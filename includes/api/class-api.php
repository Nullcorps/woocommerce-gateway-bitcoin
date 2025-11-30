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
 *
 * - TODO After x unpaid time, mark unpaid orders as failed/cancelled.
 * - TODO: There should be a global cap on how long an address can be assigned without payment. Not something to handle in this class
 * – TODO: hook into post_status changes (+count) to decide to schedule? Or call directly from API class when it assigns an Address to an Order?
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\API;

use BrianHenryIE\WP_Bitcoin_Gateway\Action_Scheduler\API_Background_Jobs_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\Action_Scheduler\Background_Jobs_Scheduling_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address_Status;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Blockchain\Rate_Limit_Exception;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Model\Addresses_Generation_Result;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Model\Check_Assigned_Addresses_For_Transactions_Result;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Model\Transaction_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Model\Wallet_Generation_Result;
use BrianHenryIE\WP_Bitcoin_Gateway\Brick\Math\BigDecimal;
use BrianHenryIE\WP_Bitcoin_Gateway\Brick\Math\RoundingMode;
use BrianHenryIE\WP_Bitcoin_Gateway\Brick\Money\Currency;
use BrianHenryIE\WP_Bitcoin_Gateway\Brick\Money\Money;
use BrianHenryIE\WP_Bitcoin_Gateway\Integrations\WooCommerce\Details_Formatter;
use BrianHenryIE\WP_Bitcoin_Gateway\Integrations\WooCommerce\Model\WC_Bitcoin_Order;
use BrianHenryIE\WP_Bitcoin_Gateway\Integrations\WooCommerce\Model\WC_Bitcoin_Order_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\Integrations\WooCommerce\Transaction_Formatter;
use BrianHenryIE\WP_Bitcoin_Gateway\Action_Scheduler\Background_Jobs;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address_Repository;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Wallet;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Wallet_Factory;
use BrianHenryIE\WP_Bitcoin_Gateway\API_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\Settings_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\Integrations\WooCommerce\Order;
use BrianHenryIE\WP_Bitcoin_Gateway\Integrations\WooCommerce\Thank_You;
use BrianHenryIE\WP_Bitcoin_Gateway\Integrations\WooCommerce\Bitcoin_Gateway;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Order;
use WC_Payment_Gateway;
use WC_Payment_Gateways;

class API implements API_Interface, API_Background_Jobs_Interface {
	use LoggerAwareTrait;

	protected Background_Jobs $background_jobs;

	/**
	 * Constructor
	 *
	 * @param Settings_Interface         $settings The plugin settings.
	 * @param LoggerInterface            $logger A PSR logger.
	 * @param Bitcoin_Wallet_Factory     $bitcoin_wallet_factory Wallet repository.
	 * @param Bitcoin_Address_Repository $bitcoin_address_repository Repository to save and fetch addresses from wp_posts.
	 * @param Blockchain_API_Interface $blockchain_api The object/client to query the blockchain for transactions
	 * @param Generate_Address_API_Interface $generate_address_api Object that does the maths to generate new addresses for a wallet.
	 * @param Exchange_Rate_API_Interface $exchange_rate_api Object/client to fetch the exchange rate
	 * @param Background_Jobs_Scheduling_Interface $background_jobs Object to schedule background jobs.
	 */
	public function __construct(
		protected Settings_Interface $settings,
		LoggerInterface $logger,
		protected Bitcoin_Wallet_Factory $bitcoin_wallet_factory,
		protected Bitcoin_Address_Repository $bitcoin_address_repository,
		protected Blockchain_API_Interface $blockchain_api,
		protected Generate_Address_API_Interface $generate_address_api,
		protected Exchange_Rate_API_Interface $exchange_rate_api,
	) {
		$this->setLogger( $logger );
	}

	/**
	 * Set the background jobs scheduler.
	 *
	 * @param Background_Jobs $background_jobs The background jobs scheduler.
	 */
	public function set_background_jobs( Background_Jobs $background_jobs ): void {
		$this->background_jobs = $background_jobs;
	}

	/**
	 * Check a gateway id and determine is it an instance of this gateway type.
	 * Used on thank you page to return early.
	 *
	 * @used-by Thank_You::print_instructions()
	 *
	 * @param string $gateway_id The id of the gateway to check.
	 */
	public function is_bitcoin_gateway( string $gateway_id ): bool {
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) || ! class_exists( WC_Payment_Gateway::class ) ) {
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
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) || ! class_exists( WC_Payment_Gateways::class ) ) {
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
	public function is_order_has_bitcoin_gateway( int|string $order_id ): bool {
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) || ! function_exists( 'wc_get_order' ) ) {
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
	 * @throws Exception
	 */
	public function get_fresh_address_for_order( WC_Order $order ): Bitcoin_Address {
		$this->logger->debug( 'Get fresh address for `shop_order:' . $order->get_id() . '`' );

		$btc_addresses = $this->get_fresh_addresses_for_gateway( $this->get_bitcoin_gateways()[ $order->get_payment_method() ] );

		if ( empty( $btc_addresses ) ) {
			throw new Exception( 'No Bitcoin addresses available.' );
		}

		$btc_address = array_shift( $btc_addresses );

		$order->add_meta_data( Order::BITCOIN_ADDRESS_META_KEY, $btc_address->get_raw_address() );
		$order->save();

		$btc_address->set_status( Bitcoin_Address_Status::ASSIGNED );

		$this->logger->info(
			sprintf(
				'Assigned `bh-bitcoin-address:%d` %s to `shop_order:%d`.',
				$this->bitcoin_address_repository->get_post_id_for_address( $btc_address->get_raw_address() ),
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
	 * @throws Exception
	 */
	public function is_fresh_address_available_for_gateway( Bitcoin_Gateway $gateway ): bool {
		// TODO: cache this.
		return count( $this->get_fresh_addresses_for_gateway( $gateway ) ) > 0;
	}

	/**
	 * Get the current status of the order's payment.
	 *
	 * As a really detailed array for printing.
	 *
	 * `array{btc_address:string, bitcoin_total:Money, btc_price_at_at_order_time:string, transactions:array<string, TransactionArray>, btc_exchange_rate:string, last_checked_time:DateTimeInterface, btc_amount_received:string, order_status_before:string}`
	 *
	 * @param WC_Order $wc_order The WooCommerce order to check.
	 * @param bool     $refresh Should the result be returned from cache or refreshed from remote APIs.
	 *
	 * @return WC_Bitcoin_Order_Interface
	 * @throws Exception
	 */
	public function get_order_details( WC_Order $wc_order, bool $refresh = true ): WC_Bitcoin_Order_Interface {

		$bitcoin_order = new WC_Bitcoin_Order( $wc_order, $this->bitcoin_address_repository );

		if ( $refresh ) {
			$this->refresh_order( $bitcoin_order );
		}

		return $bitcoin_order;
	}

	/**
	 *
	 * TODO: mempool.
	 *
	 * @throws Exception
	 */
	protected function refresh_order( WC_Bitcoin_Order_Interface $bitcoin_order ): bool {

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

		if ( ! $gateway ) {
			return false;
		}

		// $confirmations = $gateway->get_confirmations();
		$required_confirmations = 3;

		try {
			$blockchain_height = $this->blockchain_api->get_blockchain_height();
		} catch ( Exception $e ) {
			// TODO: log, notify, rate limit
			return false;
		}

		$raw_address = $bitcoin_order->get_address()->get_raw_address();

		$confirmed_value_current = $bitcoin_order->get_address()->get_confirmed_balance( $blockchain_height, $required_confirmations );

		$unconfirmed_value_current = array_reduce(
			$order_transactions_current_blockchain,
			function ( Money $carry, Transaction_Interface $transaction ) use ( $blockchain_height, $required_confirmations, $raw_address ) {
				if ( $blockchain_height - ( $transaction->get_block_height() ?? $blockchain_height ) > $required_confirmations ) {
					return $carry;
				}
				return $carry->plus( $transaction->get_value( $raw_address ) );
			},
			Money::of( 0, 'BTC' )
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

		if ( ! $bitcoin_order->is_paid() && ! is_null( $confirmed_value_current ) && ! $confirmed_value_current->isZero() ) {
			$expected        = $bitcoin_order->get_btc_total_price();
			$price_margin    = $gateway->get_price_margin_percent();
			$minimum_payment = $expected->multipliedBy( ( 100 - $price_margin ) / 100 );

			if ( $confirmed_value_current->isGreaterThan( $minimum_payment ) ) {
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
	 * @param WC_Order $order The WooCommerce order object to update.
	 * @param bool     $refresh Should saved order details be returned or remote APIs be queried.
	 *
	 * @return array<string, mixed>
	 * @throws Exception
	 * @uses \BrianHenryIE\WP_Bitcoin_Gateway\API_Interface::get_order_details()
	 * @see  Details_Formatter
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
	 * @param Currency $currency
	 *
	 * @throws Exception
	 */
	public function get_exchange_rate( Currency $currency ): ?Money {
		$transient_name = 'bh_wp_bitcoin_gateway_exchange_rate_' . $currency->getCurrencyCode();
		/** @var false|array{amount:string,currency:string} $exchange_rate_stored_transient */
		$exchange_rate_stored_transient = get_transient( $transient_name );

		if ( empty( $exchange_rate_stored_transient ) ) {
			try {
				$exchange_rate = $this->exchange_rate_api->get_exchange_rate( $currency );
			} catch ( Exception $e ) {
				// TODO: rate limit.
				return null;
			}
			set_transient( $transient_name, $exchange_rate->jsonSerialize(), HOUR_IN_SECONDS );
		} else {
			$exchange_rate = Money::of( $exchange_rate_stored_transient['amount'], $exchange_rate_stored_transient['currency'] );
		}

		return $exchange_rate;
	}

	/**
	 * Get the BTC value of another currency amount.
	 *
	 * Rounds to ~6 decimal places.
	 * Limited currency support: 'USD'|'EUR'|'GBP', maybe others.
	 *
	 * @param Money $fiat_amount This is stored in the WC_Order object as a float (as a string in meta).
	 */
	public function convert_fiat_to_btc( Money $fiat_amount ): Money {

		$exchange_rate = $this->get_exchange_rate( $fiat_amount->getCurrency() );

		if ( is_null( $exchange_rate ) ) {
			throw new Exception( 'No exchange rate available' );
		}

		// 1 BTC = xx USD.
		$exchange_rate = BigDecimal::of( '1' )->dividedBy( $exchange_rate->getAmount(), 16, RoundingMode::DOWN );

		return $fiat_amount->convertedTo( Currency::of( 'BTC' ), $exchange_rate, null, RoundingMode::DOWN );

		// This is a good number for January 2023, 0.000001 BTC = 0.02 USD.
		// TODO: Calculate the appropriate number of decimals on the fly.
		// $num_decimal_places = 6;
		// $string_result      = (string) wc_round_discount( $float_result, $num_decimal_places + 1 );
		// return $string_result;
	}

	/**
	 * Given an xpub, create the wallet post (if not already existing) and generate addresses until some fresh ones
	 * are generated.
	 *
	 * TODO: refactor this so it can handle 429 rate limiting.
	 *
	 * @param string  $master_public_key Xpub/ypub/zpub string.
	 * @param ?string $gateway_id
	 *
	 * @return Wallet_Generation_Result
	 * @throws Exception
	 */
	public function generate_new_wallet( string $master_public_key, ?string $gateway_id = null ): Wallet_Generation_Result {

		$post_id = $this->bitcoin_wallet_factory->get_post_id_for_wallet( $master_public_key )
			?? $this->bitcoin_wallet_factory->save_new( $master_public_key, $gateway_id );

		$wallet = $this->bitcoin_wallet_factory->get_by_post_id( $post_id );

		$existing_fresh_addresses = $wallet->get_fresh_addresses();

		$generated_addresses = array();

		$count = count( $wallet->get_fresh_addresses() );
		while ( $count < 20 ) {

			$generate_addresses_result = $this->generate_new_addresses_for_wallet( $wallet );
			$new_generated_addresses   = $generate_addresses_result->new_addresses;

			$generated_addresses = array_merge( $generated_addresses, $new_generated_addresses );

			$check_new_addresses_result = $this->check_addresses_for_transactions( $generated_addresses );

			++$count;
		}

		// TODO: Only return / distinguish which generated addresses are fresh.

		return new Wallet_Generation_Result(
			$wallet,
			$existing_fresh_addresses,
			$generated_addresses
		);
	}

	/**
	 * If a wallet has fewer than 20 fresh addresses available, generate some more.
	 *
	 * @see API_Interface::generate_new_addresses()
	 * @used-by CLI::generate_new_addresses()
	 * @used-by Background_Jobs::generate_new_addresses()
	 *
	 * @return Addresses_Generation_Result[]
	 */
	public function generate_new_addresses(): array {

		/**
		 * @var array<int, Addresses_Generation_Result> $results
		 */
		$results = array();

		foreach ( $this->get_bitcoin_gateways() as $gateway ) {
			$gateway_master_public_key = $gateway->get_xpub();
			$gateway_wallet_post_id    = $this->bitcoin_wallet_factory->get_post_id_for_wallet( $gateway_master_public_key );
			if ( is_null( $gateway_wallet_post_id ) ) {
				$gateway_wallet_post_id = $this->bitcoin_wallet_factory->save_new( $gateway_master_public_key, $gateway->id );
			}
			$wallet = $this->bitcoin_wallet_factory->get_by_post_id( $gateway_wallet_post_id );

			$results[] = $this->generate_new_addresses_for_wallet( $wallet );
		}

		return $results;
	}

	/**
	 * @param Bitcoin_Wallet $wallet
	 * @param int            $generate_count // TODO:  20 is the standard lookahead for wallets. cite.
	 *
	 * @throws Exception When no wallet object is found for the master public key (xpub) string.
	 */
	public function generate_new_addresses_for_wallet( Bitcoin_Wallet $wallet, int $generate_count = 20 ): Addresses_Generation_Result {

		$address_index = $wallet->get_address_index();

		$generated_addresses_post_ids = array();
		$generated_addresses_count    = 0;

		do {
			// TODO: Post increment or we will never generate address 0 like this.
			++$address_index;

			$new_address_string = $this->generate_address_api->generate_address( $wallet->get_xpub(), $address_index );

			if ( ! is_null( $this->bitcoin_address_repository->get_post_id_for_address( $new_address_string ) ) ) {
				continue;
			}

			$bitcoin_address_new_post_id = $this->bitcoin_address_repository->save_new( $new_address_string, $address_index, $wallet );

			$generated_addresses_post_ids[] = $bitcoin_address_new_post_id;
			++$generated_addresses_count;

		} while ( $generated_addresses_count < $generate_count );

		$generated_addresses = array_map(
			function ( int $post_id ): Bitcoin_Address {
				return $this->bitcoin_address_repository->get_by_post_id( $post_id );
			},
			$generated_addresses_post_ids
		);

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

		return new Addresses_Generation_Result(
			$wallet,
			$generated_addresses,
			$address_index,
		);
	}

	/**
	 * @used-by Background_Jobs::check_new_addresses_for_transactions()
	 *
	 * @throws Rate_Limit_Exception
	 *
	 * @return Check_Assigned_Addresses_For_Transactions_Result (was: array<string, array<string, Transaction_Interface>>)
	 */
	public function check_new_addresses_for_transactions(): Check_Assigned_Addresses_For_Transactions_Result {

		$addresses = array();

		// Get all wallets whose status is unknown.
		$posts = get_posts(
			array(
				'post_type'      => Bitcoin_Address::POST_TYPE,
				'post_status'    => Bitcoin_Address_Status::UNKNOWN->value,
				'posts_per_page' => 100,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		if ( empty( $posts ) ) {
			$this->logger->debug( 'No addresses with "unknown" status to check' );

			return new Check_Assigned_Addresses_For_Transactions_Result(); // TODO: return something meaningful.
		}

		foreach ( $posts as $post ) {

			$post_id = $post->ID;

			$addresses[] = $this->bitcoin_address_repository->get_by_post_id( $post_id );

		}

		return $this->check_addresses_for_transactions( $addresses );
	}

	/**
	 * @used-by Background_Jobs::check_new_addresses_for_transactions()
	 *
	 * @param Bitcoin_Address[] $addresses Array of address objects to query and update.
	 *
	 * @return Check_Assigned_Addresses_For_Transactions_Result (was array<string, array<string, Transaction_Interface>>))
	 */
	public function check_addresses_for_transactions( array $addresses ): Check_Assigned_Addresses_For_Transactions_Result {

		$result = array();

		try {
			foreach ( $addresses as $bitcoin_address ) {
				$result[ $bitcoin_address->get_raw_address() ] = $this->update_address_transactions( $bitcoin_address );
			}
		} catch ( Rate_Limit_Exception $exception ) {
			throw $exception;
		} catch ( Exception $exception ) {
			// Reschedule if we hit 429 (there will always be at least one address to check if it 429s.).
			$this->logger->debug( $exception->getMessage() );

			// Eh.
			$this->background_jobs->schedule_check_newly_generated_bitcoin_addresses_for_transactions(
				DateTimeImmutable::createFromFormat( 'U', (string) ( time() + ( 15 * constant( (string) MINUTE_IN_SECONDS ) ) ) ),
			);
		}

		// TODO: After this is complete, there could be 0 fresh addresses (e.g. if we start at index 0 but 200 addresses
		// are already used). => We really need to generate new addresses until we have some.

		// TODO: Return something useful.
		return new Check_Assigned_Addresses_For_Transactions_Result();
	}

	/**
	 * Remotely check/fetch the latest data for an address.
	 *
	 * @param Bitcoin_Address $address The address object to query.
	 *
	 * @return array<string, Transaction_Interface>
	 */
	public function update_address_transactions( Bitcoin_Address $address ): array {

		$btc_xpub_address_string = $address->get_raw_address();
		// TODO: retry on rate limit.
		try {
			$updated_transactions = $this->blockchain_api->get_transactions_received( $btc_xpub_address_string );
			$address->set_transactions( $updated_transactions );
			return $updated_transactions;
		} catch ( Exception $_exception ) {
			// API is offline.
			// TODO: log, rate limit, notify.
			// TODO: is empty array ok here?
			return $address->get_blockchain_transactions() ?? array();
		}

		// TODO: This should be in the API class.
		// if ( $is_paid ) {
		//
		// each address has one parent post (order)
		//
		// $order_post_id = $address_post->post_parent;
		// do_action( 'bh_wp_bitcoin_gateway_payment_received', $address_post_id, $order_post_id, $order_post_type );
		//
		// }
	}

	/**
	 * TODO: The return value should be a structured summary that can be used in a log message.
	 */
	public function check_assigned_addresses_for_transactions(): Check_Assigned_Addresses_For_Transactions_Result {

		foreach ( $this->bitcoin_address_repository->get_assigned_bitcoin_addresses() as $bitcoin_address ) {
			$this->update_address_transactions( $bitcoin_address );
		}
		return new Check_Assigned_Addresses_For_Transactions_Result();
	}
}
