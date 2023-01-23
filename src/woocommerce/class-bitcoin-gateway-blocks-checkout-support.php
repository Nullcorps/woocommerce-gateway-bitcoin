<?php
/**
 * Make the payment gateway available to the new WooCommerce Blocks checkout.
 *
 * Mostly just registers a script.
 *
 * @package brianhenryie/bh-wc-bitcoin-gateway
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use BrianHenryIE\WC_Bitcoin_Gateway\Settings_Interface;

/**
 * Instance of the class expected by PaymentMethodRegistry.
 *
 * @see PaymentMethodRegistry::register()
 * @see IntegrationRegistry::initialize()
 */
class Bitcoin_Gateway_Blocks_Checkout_Support extends AbstractPaymentMethodType {

	/**
	 * Used to get the plugin URL.
	 */
	protected Settings_Interface $plugin_settings;

	/**
	 * The gateway instance.
	 *
	 * @var Bitcoin_Gateway
	 */
	protected $gateway;

	/**
	 * Constructor
	 *
	 * @param Bitcoin_Gateway    $gateway The gateway instance.
	 * @param Settings_Interface $plugin_settings The plugin settings.
	 */
	public function __construct( Bitcoin_Gateway $gateway, Settings_Interface $plugin_settings ) {
		$this->plugin_settings = $plugin_settings;
		$this->gateway         = $gateway;
	}

	/**
	 * Initializes the payment method type.
	 *
	 * @see IntegrationInterface::initialize()
	 */
	public function initialize(): void {
		$this->settings = $this->gateway->settings;
		$this->name     = $this->gateway->id;
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 */
	public function is_active(): bool {
		return $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array<string>
	 */
	public function get_payment_method_script_handles(): array {

		$handle = 'bh-wc-bitcoin-gateway-blocks';

		$script_url   = $this->plugin_settings->get_plugin_url() . 'assets/js/frontend/blocks/checkout/bh-wc-bitcoin-gateway-blocks-checkout.min.js';
		$dependencies = array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities', 'wp-i18n' );
		$version      = $this->plugin_settings->get_plugin_version();

		wp_register_script( $handle, $script_url, $dependencies, $version, true );

		wp_set_script_translations( $handle, 'bh-wc-bitcoin-gateway', BH_WC_BITCOIN_GATEWAY_PATH . '/languages/' );

		return array( $handle );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * Filters the `WC_Payment_Gateway::$supports` array using the instance's `supports()` function.
	 *
	 * @see \WC_Payment_Gateway::supports()
	 *
	 * @return array{title:string, description:string, supports:array<string>}
	 */
	public function get_payment_method_data(): array {
		return array(
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'supports'    => array_filter( $this->gateway->supports, array( $this->gateway, 'supports' ) ),
		);
	}
}
