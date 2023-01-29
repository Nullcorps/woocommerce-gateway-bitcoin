<?php
/**
 * The main payment gateway class for the plugin.
 *
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce;

use BrianHenryIE\WC_Bitcoin_Gateway\API\Addresses\Bitcoin_Wallet_Factory;
use BrianHenryIE\WC_Bitcoin_Gateway\Settings_Interface;
use Exception;
use BrianHenryIE\WC_Bitcoin_Gateway\Action_Scheduler\Background_Jobs;
use BrianHenryIE\WC_Bitcoin_Gateway\API\Addresses\Bitcoin_Address;
use BrianHenryIE\WC_Bitcoin_Gateway\API_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use WC_Order;
use WC_Payment_Gateway;

/**
 * Simple instance of WC_Payment Gateway. Defines the admin settings and processes the payment.
 *
 * @see WC_Settings_API
 */
class Bitcoin_Gateway extends WC_Payment_Gateway {
	use LoggerAwareTrait;

	/**
	 * The default id for an instance of this gateway (typically there will only be one).
	 *
	 * @override WC_Settings_API::$id
	 *
	 * @var string
	 */
	public $id = 'bitcoin_gateway';

	/**
	 * Used to generate new wallets when the xpub is entered, and to fetch addresses when orders are placed.
	 *
	 * @var ?API_Interface
	 */
	protected ?API_Interface $api = null;

	/**
	 * The plugin settings.
	 *
	 * Used to read the gateway settings from wp_options before they are initialized in this class.
	 *
	 * @var Settings_Interface
	 */
	protected Settings_Interface $plugin_settings;

	/**
	 * A customisable message to show alongside the payment instructions.
	 *
	 * @var string
	 */
	protected string $instructions;

	/**
	 * Is this gateway enabled and has a payment address available.
	 *
	 * Previously we were using a static value in a method to store this, but that caused problems with tests, and
	 * would be an issue with Duplicate Payment Gateways.
	 *
	 * @used-by Bitcoin_Gateway::is_available()
	 *
	 * @var ?bool
	 */
	protected ?bool $is_available = null;

	/**
	 * Constructor for the gateway.
	 *
	 * @param ?API_Interface $api The main plugin functions.
	 */
	public function __construct( ?API_Interface $api = null ) {
		// TODO: Set the logger externally.
		$this->setLogger( new NullLogger() );

		$this->api = $api ?? $GLOBALS['bh_wc_bitcoin_gateway'];

		$this->plugin_settings = new \BrianHenryIE\WC_Bitcoin_Gateway\API\Settings();

		$this->icon               = plugins_url( 'assets/bitcoin.png', 'bh-wc-bitcoin-gateway/bh-wc-bitcoin-gateway.php' );
		$this->has_fields         = false;
		$this->method_title       = __( 'Bitcoin', 'bh-wc-bitcoin-gateway' );
		$this->method_description = __( 'Accept Bitcoin payments. Customers are shown payment instructions and a QR code. Orders are marked paid once payment is confirmed on the blockchain.', 'bh-wc-bitcoin-gateway' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions', $this->description );

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'admin_notices', array( $this, 'display_errors' ), 9999 );
	}

	/**
	 * When saving the options, if the xpub is changed, initiate a background job to generate addresses.
	 *
	 * @see \WC_Settings_API::process_admin_options()
	 *
	 * @return bool
	 */
	public function process_admin_options() {

		$xpub_before = $this->get_xpub();

		$is_processed = parent::process_admin_options();
		$xpub_after   = $this->get_xpub();

		if ( $xpub_before !== $xpub_after && ! empty( $xpub_after ) ) {
			$gateway_name = $this->get_method_title() === $this->get_method_description() ? $this->get_method_title() : $this->get_method_title() . ' (' . $this->get_method_description() . ')';
			$this->logger->info(
				"New xpub key set for gateway $gateway_name: $xpub_after",
				array(
					'gateway_id'  => $this->id,
					'xpub_before' => $xpub_before,
					'xpub_after'  => $xpub_after,
				)
			);

			if ( ! is_null( $this->api ) ) {
				$this->api->generate_new_wallet( $xpub_after, $this->id );
				$this->api->generate_new_addresses_for_wallet( $xpub_after, 2 );
			}

			// TODO: maybe mark the previous xpub's wallet as "inactive". (although it could be in use in another instance of the gateway).
		}

		return $is_processed;
	}

	/**
	 * Initialize Gateway Settings Form Fields
	 *
	 * @see WC_Settings_API::init_form_fields()
	 *
	 * @return void
	 */
	public function init_form_fields() {

		$settings_fields = array(

			'enabled'               => array(
				'title'   => __( 'Enable/Disable', 'bh-wc-bitcoin-gateway' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Bitcoin Payment', 'bh-wc-bitcoin-gateway' ),
				'default' => 'yes',
			),

			'title'                 => array(
				'title'       => __( 'Title', 'bh-wc-bitcoin-gateway' ),
				'type'        => 'text',
				'description' => __( 'The payment method title the customer sees during checkout.', 'bh-wc-bitcoin-gateway' ),
				'default'     => __( 'Bitcoin', 'bh-wc-bitcoin-gateway' ),
				'desc_tip'    => false,
			),

			'description'           => array(
				'title'       => __( 'Description', 'bh-wc-bitcoin-gateway' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will beside the Place Order button.', 'bh-wc-bitcoin-gateway' ),
				'default'     => __( 'Pay quickly and easily with Bitcoin', 'bh-wc-bitcoin-gateway' ),
				'desc_tip'    => false,
			),

			'instructions'          => array(
				'title'       => __( 'Instructions', 'bh-wc-bitcoin-gateway' ),
				'type'        => 'textarea',
				'description' => __( 'Additional instructions to appear alongside the payment address and amount, after the order has been placed but before payment has been made.', 'bh-wc-bitcoin-gateway' ),
				'default'     => 'NB: Please only send Bitcoin, which always has the ticker BTC, not any of the many clones. If you send coins other than Bitcoin (e.g. Bitcoin Cash) then those coins will be lost and your order will still not be paid.',
				'desc_tip'    => false,
			),

			'xpub'                  => array(
				'title'       => __( 'xpub', 'bh-wc-bitcoin-gateway' ),
				'type'        => 'text',
				'description' => __( 'The xpub/zpub (master public key) for your HD wallet, which we use to locally generate the addresses to pay to (no API calls). Find it in Electrum under menu:wallet/information. It looks like <code>xpub1a2bc3d4longalphanumericstring</code>', 'bh-wc-bitcoin-gateway' ),
				'default'     => '',
				'desc_tip'    => false,
			),
			// TODO: Show balance here.

			'btc_rounding_decimals' => array(
				'title'       => __( 'btc-rounding-decimals', 'bh-wc-bitcoin-gateway' ),
				'type'        => 'text',
				'description' => __( 'Integer, somewhere around 6 or 7 is probably ideal currently.', 'bh-wc-bitcoin-gateway' ),
				'default'     => '7',
				'desc_tip'    => false,
			),

			'price_margin'          => array(
				'title'       => __( 'price-margin', 'bh-wc-bitcoin-gateway' ),
				'type'        => 'text',
				'description' => __( 'A percentage amount of shortfall from the shown price which will be accepted to allow for rounding errors. Recommend value between 0 and 3', 'bh-wc-bitcoin-gateway' ),
				'default'     => '2',
				'desc_tip'    => false,
			),

		);
		$saved_xpub = $this->plugin_settings->get_xpub( $this->id );
		if ( ! empty( $saved_xpub ) ) {
			$settings_fields['xpub']['description'] = $saved_xpub;
		}

		$settings_fields['xpub']['description'] = $settings_fields['xpub']['description'] . ' <a href="' . esc_url( admin_url( 'edit.php?post_type=bh-bitcoin-address' ) ) . '">View addresses</a>';

		$log_levels        = array( 'none', LogLevel::ERROR, LogLevel::WARNING, LogLevel::NOTICE, LogLevel::INFO, LogLevel::DEBUG );
		$log_levels_option = array();
		foreach ( $log_levels as $log_level ) {
			$log_levels_option[ $log_level ] = ucfirst( $log_level );
		}

		$settings_fields['log_level'] = array(
			'title'       => __( 'Log Level', 'text-domain' ),
			'label'       => __( 'Enable Logging', 'text-domain' ),
			'type'        => 'select',
			'options'     => $log_levels_option,
			'description' => __( 'Increasingly detailed levels of logs. ', 'bh-wc-bitcoin-gateway' ) . '<a href="' . admin_url( 'admin.php?page=bh-wc-bitcoin-gateway-logs' ) . '">View Logs</a>',
			'desc_tip'    => false,
			'default'     => 'info',
		);

		$this->form_fields = apply_filters( 'wc_gateway_bitcoin_form_fields', $settings_fields, $this->id );
	}


	/**
	 * Returns false when the gateway is not configured / has no addresses to use.
	 *
	 * @see WC_Payment_Gateways::get_available_payment_gateways()
	 *
	 * @return bool
	 */
	public function is_available() {

		if ( is_null( $this->api ) ) {
			return false;
		}

		if ( is_null( $this->is_available ) ) {
			$result             = parent::is_available() && $this->api->is_fresh_address_available_for_gateway( $this );
			$this->is_available = $result;
		} else {
			$result = $this->is_available;
		}

		return $result;
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id The id of the order being paid.
	 *
	 * @return array{result:string, redirect:string}
	 * @throws Exception Throws an exception when no address is available (which is caught by WooCommerce and displayed at checkout).
	 */
	public function process_payment( $order_id ) {

		$order = wc_get_order( $order_id );

		if ( ! ( $order instanceof WC_Order ) ) {
			// This should never happen.
			throw new Exception( __( 'Error creating order.', 'bh-wc-bitcoin-gateway' ) );
		}

		if ( is_null( $this->api ) ) {
			throw new Exception( __( 'API unavailable for new Bitcoin gateway order.', 'bh-wc-bitcoin-gateway' ) );
		}

		$api = $this->api;

		/**
		 * There should never really be an exception here, since the availability of a fresh address was checked before
		 * offering the option to pay by Bitcoin.
		 *
		 * @see Bitcoin_Gateway::is_available()
		 */
		try {
			/**
			 * This sets the order meta value inside the function.
			 *
			 * @see Order::BITCOIN_ADDRESS_META_KEY
			 * @see Bitcoin_Address::get_raw_address()
			 */
			$btc_address = $api->get_fresh_address_for_order( $order );
		} catch ( Exception $e ) {
			// TODO: Log.
			throw new Exception( 'Unable to find Bitcoin address to send to. Please choose another payment method.' );
		}

		// Record the exchange rate at the time the order was placed.
		$order->add_meta_data( Order::EXCHANGE_RATE_AT_TIME_OF_PURCHASE_META_KEY, $api->get_exchange_rate( $order->get_currency() ) );

		$btc_total = $api->convert_fiat_to_btc( $order->get_currency(), $order->get_total() );

		$order->add_meta_data( Order::ORDER_TOTAL_BITCOIN_AT_TIME_OF_PURCHASE_META_KEY, $btc_total );

		// Mark as on-hold (we're awaiting the payment).
		/* translators: %F: The order total in BTC */
		$order->update_status( 'on-hold', sprintf( __( 'Awaiting Bitcoin payment of %F to address: ', 'bh-wc-bitcoin-gateway' ), $btc_total ) . '<a target="_blank" href="https://www.blockchain.com/btc/address/' . $btc_address->get_raw_address() . "\">{$btc_address->get_raw_address()}</a>.\n\n" );

		$order->save();

		// Reduce stock levels.
		wc_reduce_stock_levels( $order_id );

		// Remove cart.
		WC()->cart->empty_cart();

		// Return thankyou redirect.
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Get the instructions configured by the admin to display on the Thank You page.
	 *
	 * "Additional instructions to appear alongside the payment address and amount, after the order has been placed but before payment has been made."
	 *
	 * @return string
	 */
	public function get_instructions(): string {
		return $this->settings['instructions'] ?? '';
	}

	/**
	 * Returns the configured xpub for the gateway, so new addresses can be generated.
	 *
	 * @used-by API::generate_new_addresses_for_wallet()
	 *
	 * @return string
	 */
	public function get_xpub(): string {
		return $this->settings['xpub'];
	}

	/**
	 * Price margin is the allowable difference between the amount received and the amount expected.
	 *
	 * @used-by API::get_order_details()
	 *
	 * @return float
	 */
	public function get_price_margin_percent(): float {
		return floatval( $this->settings['price_margin'] ?? 2.0 );
	}
}
