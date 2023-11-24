<?php
/**
 * Main plugin functions for:
 * * checking is a gateway a Bitcoin gateway
 * * generating new wallets
 * * converting fiat<->BTC
 * * generating/getting new addresses for orders
 * * checking addresses for transactions
 * * getting order details for display
 *
 * @package    brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\API;

use BrianHenryIE\WP_Bitcoin_Gateway\API\Blockchain\Blockstream_Info_API;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Model\Bitcoin_Order;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Model\Bitcoin_Order_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Model\Transaction_Interface;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use BrianHenryIE\WP_Bitcoin_Gateway\Action_Scheduler\Background_Jobs;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address_Factory;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Wallet;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Wallet_Factory;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Exchange_Rate\Bitfinex_API;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\BitWasp_API;
use BrianHenryIE\WP_Bitcoin_Gateway\API_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\Settings_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce\Order;
use BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce\Thank_You;
use BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce\Bitcoin_Gateway;
use JsonException;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Order;
use WC_Payment_Gateway;
use WC_Payment_Gateways;

/**
 *
 */
class API implements API_Interface {
	use LoggerAwareTrait;

	/**
	 * Plugin settings.
	 */
	protected Settings_Interface $settings;

	/**
	 * API to query transactions.
	 */
	protected Blockchain_API_Interface $blockchain_api;

	/**
	 * API to calculate prices.
	 */
	protected Exchange_Rate_API_Interface $exchange_rate_api;

	/**
	 * Object to derive payment addresses.
	 */
	protected Generate_Address_API_Interface $generate_address_api;

	/**
	 * Factory to save and fetch wallets from wp_posts.
	 */
	protected Bitcoin_Wallet_Factory $bitcoin_wallet_factory;

	/**
	 * Factory to save and fetch addresses from wp_posts.
	 */
	protected Bitcoin_Address_Factory $bitcoin_address_factory;

	/**
	 * Constructor
	 *
	 * @param Settings_Interface      $settings The plugin settings.
	 * @param LoggerInterface         $logger A PSR logger.
	 * @param Bitcoin_Wallet_Factory  $bitcoin_wallet_factory Wallet factory.
	 * @param Bitcoin_Address_Factory $bitcoin_address_factory Address factory.
	 */
	public function __construct(
		Settings_Interface $settings,
		LoggerInterface $logger,
		Bitcoin_Wallet_Factory $bitcoin_wallet_factory,
		Bitcoin_Address_Factory $bitcoin_address_factory,
		Blockchain_API_Interface $blockchain_api,
		Generate_Address_API_Interface $generate_address_api,
		Exchange_Rate_API_Interface $exchange_rate_api
	) {
		$this->setLogger( $logger );
		$this->settings = $settings;

		$this->bitcoin_wallet_factory  = $bitcoin_wallet_factory;
		$this->bitcoin_address_factory = $bitcoin_address_factory;

		$this->blockchain_api       = $blockchain_api;
		$this->generate_address_api = $generate_address_api;
		$this->exchange_rate_api    = $exchange_rate_api;
	}

	/**
	 * Check a gateway id and determine is it an instance of this gateway type.
	 * Used on thank you page to return early.
	 *
	 * @used-by Thank_You::print_instructions()
	 *
	 * @param string $gateway_id The id of the gateway to check.
	 *
	 * @return bool
	 */
	public function is_bitcoin_gateway( string $gateway_id ): bool {
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			return false;
		}

		$bitcoin_gateways = $this->get_bitcoin_gateways();

		$gateway_ids = array_map(
			function ( WC_Payment_Gateway $gateway ): string {
				return $gateway->id;
			},
			$bitcoin_gateways
		);

		return in_array( $gateway_id, $gateway_ids, true );
	}

	/**
	 * Get all instances of the Bitcoin gateway.
	 * (typically there is only one).
	 *
	 * @return array<string, Bitcoin_Gateway>
	 */
	public function get_bitcoin_gateways(): array {
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			return array();
		}

		$payment_gateways = WC_Payment_Gateways::instance()->payment_gateways();
		$bitcoin_gateways = array();
		foreach ( $payment_gateways as $gateway ) {
			if ( $gateway instanceof Bitcoin_Gateway ) {
				$bitcoin_gateways[ $gateway->id ] = $gateway;
			}
		}

		return $bitcoin_gateways;
	}

	/**
	 * Given an order id, determine is the order's gateway an instance of this Bitcoin gateway.
	 *
	 * @see https://github.com/BrianHenryIE/bh-wp-duplicate-payment-gateways
	 *
	 * @param int $order_id The id of the (presumed) WooCommerce order to check.
	 */
	public function is_order_has_bitcoin_gateway( int $order_id ): bool {
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			return false;
		}

		$order = wc_get_order( $order_id );

		if ( ! ( $order instanceof WC_Order ) ) {
			// Unlikely.
			return false;
		}

		$payment_gateway_id = $order->get_payment_method();

		if ( ! $this->is_bitcoin_gateway( $payment_gateway_id ) ) {
			// Exit, this isn't for us.
			return false;
		}

		return true;
	}

	/**
	 * Fetches an unused address from the cache, or generates a new one if none are available.
	 *
	 * Called inside the "place order" function, then it can throw an exception.
	 * if there's a problem and the user can immediately choose another payment method.
	 *
	 * Load our already generated fresh list.
	 * Check with a remote API that it has not been used.
	 * Save it to the order metadata.
	 * Save it locally as used.
	 * Maybe schedule more address generation.
	 * Return it to be used in an order.
	 *
	 * @used-by Bitcoin_Gateway::process_payment()
	 *
	 * @param WC_Order $order The order that will use the address.
	 *
	 * @return Bitcoin_Address
	 *
	 * @throws JsonException
	 */
	public function get_fresh_address_for_order( WC_Order $order ): Bitcoin_Address {
		$this->logger->debug( 'Get fresh address for `shop_order:' . $order->get_id() . '`' );

		$btc_addresses = $this->get_fresh_addresses_for_gateway( $this->get_bitcoin_gateways()[ $order->get_payment_method() ] );

		$btc_address = array_shift( $btc_addresses );

		$order->add_meta_data( Order::BITCOIN_ADDRESS_META_KEY, $btc_address->get_raw_address() );
		$order->save();

		$btc_address->set_status( 'assigned' );

		$this->logger->info(
			sprintf(
				'Assigned `bh-bitcoin-address:%d` %s to `shop_order:%d`.',
				$this->bitcoin_address_factory->get_post_id_for_address( $btc_address->get_raw_address() ),
				$btc_address->get_raw_address(),
				$order->get_id()
			)
		);

		return $btc_address;
	}

	/**
	 * @param Bitcoin_Gateway $gateway
	 *
	 * @return Bitcoin_Address[]
	 * @throws Exception
	 */
	public function get_fresh_addresses_for_gateway( Bitcoin_Gateway $gateway ): array {

		if ( empty( $gateway->get_xpub() ) ) {
			$this->logger->debug( "No master public key set on gateway {$gateway->id}", array( 'gateway' => $gateway ) );
			return array();
		}

		$wallet_post_id = $this->bitcoin_wallet_factory->get_post_id_for_wallet( $gateway->get_xpub() )
						?? $this->bitcoin_wallet_factory->save_new( $gateway->get_xpub(), $gateway->id );

		$wallet = $this->bitcoin_wallet_factory->get_by_post_id( $wallet_post_id );

		return $wallet->get_fresh_addresses();
	}

	/**
	 * Check do we have at least one address already generated and ready to use.
	 *
	 * @param Bitcoin_Gateway $gateway The gateway id the address is for.
	 *
	 * @used-by Bitcoin_Gateway::is_available()
	 *
	 * @return bool
	 */
	public function is_fresh_address_available_for_gateway( Bitcoin_Gateway $gateway ): bool {
		return count( $this->get_fresh_addresses_for_gateway( $gateway ) ) > 0;
	}

	/**
	 * Get the current status of the order's payment.
	 *
	 * As a really detailed array for printing.
	 *
	 * `array{btc_address:string, bitcoin_total:string, btc_price_at_at_order_time:string, transactions:array<string, TransactionArray>, btc_exchange_rate:string, last_checked_time:DateTimeInterface, btc_amount_received:string, order_status_before:string}`
	 *
	 * @param WC_Order $wc_order The WooCommerce order to check.
	 * @param bool     $refresh Should the result be returned from cache or refreshed from remote APIs.
	 *
	 * @return Bitcoin_Order_Interface
	 * @throws Exception
	 */
	public function get_order_details( WC_Order $wc_order, bool $refresh = true ): Bitcoin_Order_Interface {

		$bitcoin_order = new Bitcoin_Order( $wc_order, $this->bitcoin_address_factory );

		if ( $refresh ) {
			$this->refresh_order( $bitcoin_order );
		}

		return $bitcoin_order;
	}

	/**
	 *
	 * TODO: mempool.
	 */
	protected function refresh_order( Bitcoin_Order_Interface $bitcoin_order ): bool {

		$updated = false;

		$time_now = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );

		$order_transactions_before = $bitcoin_order->get_address()->get_blockchain_transactions();

		if ( is_null( $order_transactions_before ) ) {
			$this->logger->debug( 'Checking for the first time' );
			$order_transactions_before = array();
		}

		/** @var array<string, Transaction_Interface> $address_transactions_current */
		$address_transactions_current = $this->update_address_transactions( $bitcoin_order->get_address() );

		// TODO: Check are any previous transactions no longer present!!!

		// Filter to transactions that occurred after the order was placed.
		$order_transactions_current = array();
		foreach ( $address_transactions_current as $txid => $transaction ) {
			// TODO: maybe use block height at order creation rather than date?
			// TODO: be careful with timezones.
			if ( $transaction->get_time() > $bitcoin_order->get_date_created() ) {
				$order_transactions_current[ $txid ] = $transaction;
			}
		}

		$order_transactions_current_mempool = array_filter(
			$address_transactions_current,
			function ( Transaction_Interface $transaction ): bool {
				return is_null( $transaction->get_block_height() );
			}
		);

		$order_transactions_current_blockchain = array_filter(
			$address_transactions_current,
			function ( Transaction_Interface $transaction ): bool {
				return ! is_null( $transaction->get_block_height() );
			}
		);

		$gateway = $bitcoin_order->get_gateway();

		// $confirmations = $gateway->get_confirmations();
		$required_confirmations = 3;

		$blockchain_height = $this->blockchain_api->get_blockchain_height();

		$raw_address = $bitcoin_order->get_address()->get_raw_address();

		$confirmed_value_current = $bitcoin_order->get_address()->get_confirmed_balance( $blockchain_height, $required_confirmations );

		$unconfirmed_value_current = array_reduce(
			$order_transactions_current_blockchain,
			function ( float $carry, Transaction_Interface $transaction ) use ( $blockchain_height, $required_confirmations, $raw_address ) {
				if ( $blockchain_height - ( $transaction->get_block_height() ?? $blockchain_height ) > $required_confirmations ) {
					return $carry;
				}
				return $carry + $transaction->get_value( $raw_address );
			},
			0.0
		);

		// Filter to transactions that have just been seen, so we can record them in notes.
		$new_order_transactions = array();
		foreach ( $order_transactions_current as $txid => $transaction ) {
			if ( ! isset( $order_transactions_before[ $txid ] ) ) {
				$new_order_transactions[ $txid ] = $transaction;
			}
		}

		$transaction_formatter = new Transaction_Formatter();

		// Add a note saying "one new transactions seen, unconfirmed total =, confirmed total = ...".
		$note = '';
		if ( ! empty( $new_order_transactions ) ) {
			$updated = true;
			$note   .= $transaction_formatter->get_order_note( $new_order_transactions );
		}

		if ( ! empty( $note ) ) {
			$this->logger->info(
				$note,
				array(
					'order_id' => $bitcoin_order->get_id(),
					'updates'  => $order_transactions_current,
				)
			);

			$bitcoin_order->add_order_note( $note );
		}

		if ( ! $bitcoin_order->is_paid() && $confirmed_value_current > 0 ) {
			$expected        = $bitcoin_order->get_btc_total_price();
			$price_margin    = $gateway->get_price_margin_percent();
			$minimum_payment = $expected * ( 100 - $price_margin ) / 100;

			if ( $confirmed_value_current > $minimum_payment ) {
				$bitcoin_order->payment_complete( $order_transactions_current[ array_key_last( $order_transactions_current ) ]->get_txid() );
				$this->logger->info( "`shop_order:{$bitcoin_order->get_id()}` has been marked paid.", array( 'order_id' => $bitcoin_order->get_id() ) );

				$updated = true;
			}
		}

		if ( $updated ) {
			$bitcoin_order->set_amount_received( $confirmed_value_current );
		}
		$bitcoin_order->set_last_checked_time( $time_now );

		$bitcoin_order->save();

		return $updated;
	}


	/**
	 * Get order details for printing in HTML templates.
	 *
	 * Returns an array of:
	 * * html formatted values
	 * * raw values that are known to be used in the templates
	 * * objects the values are from
	 *
	 * @uses \BrianHenryIE\WP_Bitcoin_Gateway\API_Interface::get_order_details()
	 * @see Details_Formatter
	 *
	 * @param WC_Order $order The WooCommerce order object to update.
	 * @param bool     $refresh Should saved order details be returned or remote APIs be queried.
	 *
	 * @return array<string, mixed>
	 */
	public function get_formatted_order_details( WC_Order $order, bool $refresh = true ): array {

		$order_details = $this->get_order_details( $order, $refresh );

		$formatted = new Details_Formatter( $order_details );

		// HTML formatted data.
		$result = $formatted->to_array();

		// Raw data.
		$result['btc_total']           = $order_details->get_btc_total_price();
		$result['btc_exchange_rate']   = $order_details->get_btc_exchange_rate();
		$result['btc_address']         = $order_details->get_address()->get_raw_address();
		$result['transactions']        = $order_details->get_address()->get_blockchain_transactions();
		$result['btc_amount_received'] = $order_details->get_address()->get_amount_received() ?? 'unknown';

		// Objects.
		$result['order']         = $order;
		$result['bitcoin_order'] = $order_details;

		return $result;
	}

	/**
	 * Return the cached exchange rate, or fetch it.
	 * Cache for one hour.
	 *
	 * Value of 1 BTC.
	 *
	 * @param string $currency
	 *
	 * @throws Exception
	 */
	public function get_exchange_rate( string $currency ): string {
		$currency       = strtoupper( $currency );
		$transient_name = 'bh_wp_bitcoin_gateway_exchange_rate_' . $currency;
		$exchange_rate  = get_transient( $transient_name );

		if ( empty( $exchange_rate ) ) {
			$exchange_rate = $this->exchange_rate_api->get_exchange_rate( $currency );
			set_transient( $transient_name, $exchange_rate, HOUR_IN_SECONDS );
		}

		return $exchange_rate;
	}

	/**
	 * Get the BTC value of another currency amount.
	 *
	 * Rounds to ~6 decimal places.
	 *
	 * @param string $currency 'USD'|'EUR'|'GBP', maybe others.
	 * @param float  $fiat_amount This is stored in the WC_Order object as a float (as a string in meta).
	 *
	 * @return string Bitcoin amount.
	 */
	public function convert_fiat_to_btc( string $currency, float $fiat_amount = 1.0 ): string {

		// 1 BTC = xx USD.
		$exchange_rate = $this->get_exchange_rate( $currency );

		$float_result = $fiat_amount / floatval( $exchange_rate );

		// This is a good number for January 2023, 0.000001 BTC = 0.02 USD.
		// TODO: Calculate the appropriate number of decimals on the fly.
		$num_decimal_places = 6;
		$string_result      = (string) wc_round_discount( $float_result, $num_decimal_places + 1 );
		return $string_result;
	}

	/**
	 * Given an xpub, create the wallet post (if not already existing) and generate addresses until some fresh ones
	 * are generated.
	 *
	 * TODO: refactor this so it can handle 429 rate limiting.
	 *
	 * @param string  $xpub
	 * @param ?string $gateway_id
	 *
	 * @return array{wallet: Bitcoin_Wallet, wallet_post_id: int, existing_fresh_addresses:array<Bitcoin_Address>, generated_addresses:array<Bitcoin_Address>}
	 * @throws Exception
	 */
	public function generate_new_wallet( string $xpub, string $gateway_id = null ): array {

		$result = array();

		$post_id = $this->bitcoin_wallet_factory->get_post_id_for_wallet( $xpub )
			?? $this->bitcoin_wallet_factory->save_new( $xpub, $gateway_id );

		$wallet = $this->bitcoin_wallet_factory->get_by_post_id( $post_id );

		$result['wallet'] = $wallet;

		$existing_fresh_addresses = $wallet->get_fresh_addresses();

		$generated_addresses = array();

		while ( count( $wallet->get_fresh_addresses() ) < 20 ) {

			$generate_addresses_result = $this->generate_new_addresses_for_wallet( $xpub );
			$new_generated_addresses   = $generate_addresses_result['generated_addresses'];

			$generated_addresses = array_merge( $generated_addresses, $new_generated_addresses );

			$check_new_addresses_result = $this->check_addresses_for_transactions( $generated_addresses );
		}

		$result['existing_fresh_addresses'] = $existing_fresh_addresses;

		// TODO: Only return / distinguish which generated addresses are fresh.
		$result['generated_addresses'] = $generated_addresses;

		$result['wallet_post_id'] = $post_id;

		return $result;
	}


	/**
	 * If a wallet has fewer than 20 fresh addresses available, generate some more.
	 *
	 * @see API_Interface::generate_new_addresses()
	 * @used-by CLI::generate_new_addresses()
	 * @used-by Background_Jobs::generate_new_addresses()
	 *
	 * @return array<string, array{}|array{wallet_post_id:int, new_addresses: array{gateway_id:string, xpub:string, generated_addresses:array<Bitcoin_Address>, generated_addresses_count:int, generated_addresses_post_ids:array<int>, address_index:int}}>
	 */
	public function generate_new_addresses(): array {

		$result = array();

		foreach ( $this->get_bitcoin_gateways() as $gateway ) {

			$result[ $gateway->id ] = array();

			$wallet_address = $gateway->get_xpub();

			$wallet_post_id = $this->bitcoin_wallet_factory->get_post_id_for_wallet( $wallet_address );

			if ( is_null( $wallet_post_id ) ) {
				try {
					$wallet_post_id = $this->bitcoin_wallet_factory->save_new( $wallet_address, $gateway->id );
				} catch ( Exception $exception ) {
					$this->logger->error( 'Failed to save new wallet.' );
					continue;
				}
			}

			$result[ $gateway->id ]['wallet_post_id'] = $wallet_post_id;

			try {
				$wallet = $this->bitcoin_wallet_factory->get_by_post_id( $wallet_post_id );
			} catch ( Exception $exception ) {
				$this->logger->error( $exception->getMessage(), array( 'exception' => $exception ) );
				continue;
			}

			$fresh_addresses = $wallet->get_fresh_addresses();

			if ( count( $fresh_addresses ) > 20 ) {
				continue;
			}

			$generated_addresses_result = $this->generate_new_addresses_for_wallet( $gateway->get_xpub() );

			$generated_addresses = $generated_addresses_result['generated_addresses'];

			$result[ $gateway->id ]['new_addresses'] = $generated_addresses;

			$this->check_addresses_for_transactions( $generated_addresses );
		}

		return $result;
	}

	/**
	 * @param string $master_public_key
	 * @param int    $generate_count // TODO:  20 is the standard lookahead for wallets. cite.
	 *
	 * @return array{xpub:string, generated_addresses:array<Bitcoin_Address>, generated_addresses_count:int, generated_addresses_post_ids:array<int>, address_index:int}
	 *
	 * @throws Exception When no wallet object is found for the master public key (xpub) string.
	 */
	public function generate_new_addresses_for_wallet( string $master_public_key, int $generate_count = 20 ): array {

		$result = array();

		$result['xpub'] = $master_public_key;

		$wallet_post_id = $this->bitcoin_wallet_factory->get_post_id_for_wallet( $master_public_key )
			?? $this->bitcoin_wallet_factory->save_new( $master_public_key );

		$wallet = $this->bitcoin_wallet_factory->get_by_post_id( $wallet_post_id );

		$address_index = $wallet->get_address_index();

		$generated_addresses_post_ids = array();
		$generated_addresses_count    = 0;

		do {

			// TODO: Post increment or we will never generate address 0 like this.
			++$address_index;

			$new_address = $this->generate_address_api->generate_address( $master_public_key, $address_index );

			if ( ! is_null( $this->bitcoin_address_factory->get_post_id_for_address( $new_address ) ) ) {
				continue;
			}

			$bitcoin_address_new_post_id = $this->bitcoin_address_factory->save_new( $new_address, $address_index, $wallet );

			$generated_addresses_post_ids[] = $bitcoin_address_new_post_id;
			++$generated_addresses_count;

		} while ( $generated_addresses_count < $generate_count );

		$result['generated_addresses_count']    = $generated_addresses_count;
		$result['generated_addresses_post_ids'] = $generated_addresses_post_ids;
		$result['generated_addresses']          = array_map(
			function ( int $post_id ): Bitcoin_Address {
				return $this->bitcoin_address_factory->get_by_post_id( $post_id );
			},
			$generated_addresses_post_ids
		);
		$result['address_index']                = $address_index;

		$wallet->set_address_index( $address_index );

		if ( $generate_count > 0 ) {
			// Check the new addresses for transactions etc.
			$this->check_new_addresses_for_transactions();

			// Schedule more generation after it determines how many unused addresses are available.
			if ( count( $wallet->get_fresh_addresses() ) < 20 ) {

				$hook = Background_Jobs::GENERATE_NEW_ADDRESSES_HOOK;
				if ( ! as_has_scheduled_action( $hook ) ) {
					as_schedule_single_action( time(), $hook );
					$this->logger->debug( 'New generate new addresses background job scheduled.' );
				}
			}
		}

		return $result;
	}

	/**
	 * @used-by Background_Jobs::check_new_addresses_for_transactions()
	 *
	 * @return array<string, Transaction_Interface>
	 */
	public function check_new_addresses_for_transactions(): array {

		$addresses = array();

		// Get all wallets whose status is unknown.
		$posts = get_posts(
			array(
				'post_type'      => Bitcoin_Address::POST_TYPE,
				'post_status'    => 'unknown',
				'posts_per_page' => 100,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		if ( empty( $posts ) ) {
			$this->logger->debug( 'No addresses with "unknown" status to check' );

			return array(); // TODO: return something meaningful.
		}

		foreach ( $posts as $post ) {

			$post_id = $post->ID;

			$addresses[] = $this->bitcoin_address_factory->get_by_post_id( $post_id );

		}

		return $this->check_addresses_for_transactions( $addresses );
	}

	/**
	 * @used-by Background_Jobs::check_new_addresses_for_transactions()
	 *
	 * @param Bitcoin_Address[] $addresses Array of address objects to query and update.
	 *
	 * @return array<string, Transaction_Interface>
	 */
	public function check_addresses_for_transactions( array $addresses ): array {

		$result = array();

		try {
			foreach ( $addresses as $bitcoin_address ) {
				$result[ $bitcoin_address->get_raw_address() ] = $this->update_address_transactions( $bitcoin_address );
			}
		} catch ( Exception $exception ) {
			// Reschedule if we hit 429 (there will always be at least one address to check if it 429s.).
			$this->logger->debug( $exception->getMessage() );

			$hook = Background_Jobs::CHECK_NEW_ADDRESSES_TRANSACTIONS_HOOK;
			if ( ! as_has_scheduled_action( $hook ) ) {
				// TODO: Add new scheduled time to log.
				$this->logger->debug( 'Exception during checking addresses for transactions, scheduling new background job' );
				// TODO: Base the new time of the returned 429 header.
				as_schedule_single_action( time() + ( 10 * MINUTE_IN_SECONDS ), $hook );
			}
		}

		// TODO: After this is complete, there could be 0 fresh addresses (e.g. if we start at index 0 but 200 addresses
		// are already used). => We really need to generate new addresses until we have some.

		// TODO: Return something useful.
		return $result;
	}

	/**
	 * Remotely check/fetch the latest data for an address.
	 *
	 * @param Bitcoin_Address $address The address object to query.
	 *
	 * @return array<string, Transaction_Interface>
	 *
	 * @throws JsonException
	 */
	public function update_address_transactions( Bitcoin_Address $address ): array {

		$btc_xpub_address_string = $address->get_raw_address();

		// TODO: retry on rate limit.
		$transactions = $this->blockchain_api->get_transactions_received( $btc_xpub_address_string );

		$address->set_transactions( $transactions );

		return $transactions;
	}

	/**
	 * The PHP GMP extension is required to derive the payment addresses. This function
	 * checks is it present.
	 *
	 * @see https://github.com/Bit-Wasp/bitcoin-php
	 * @see https://www.php.net/manual/en/book.gmp.php
	 *
	 * @see gmp_init()
	 */
	public function is_server_has_dependencies(): bool {
		return function_exists( 'gmp_init' );
	}
}
