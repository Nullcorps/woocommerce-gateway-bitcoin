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
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway\API;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use BrianHenryIE\WC_Bitcoin_Gateway\Action_Scheduler\Background_Jobs;
use BrianHenryIE\WC_Bitcoin_Gateway\API\Addresses\Bitcoin_Address;
use BrianHenryIE\WC_Bitcoin_Gateway\API\Addresses\Bitcoin_Address_Factory;
use BrianHenryIE\WC_Bitcoin_Gateway\API\Addresses\Bitcoin_Wallet;
use BrianHenryIE\WC_Bitcoin_Gateway\API\Addresses\Bitcoin_Wallet_Factory;
use BrianHenryIE\WC_Bitcoin_Gateway\API\Exchange_Rate\Bitfinex_API;
use BrianHenryIE\WC_Bitcoin_Gateway\API\Addresses\BitWasp_API;
use BrianHenryIE\WC_Bitcoin_Gateway\API\Blockchain\SoChain_API;
use BrianHenryIE\WC_Bitcoin_Gateway\API_Interface;
use BrianHenryIE\WC_Bitcoin_Gateway\Settings_Interface;
use BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\Order;
use BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\Thank_You;
use BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\Bitcoin_Gateway;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Order;
use WC_Payment_Gateways;

/**
 * @phpstan-import-type TransactionArray from API_Interface as TransactionArray
 */
class API implements API_Interface {
	use LoggerAwareTrait;

	protected Settings_Interface $settings;

	protected Blockchain_API_Interface $bitcoin_api;

	protected Exchange_Rate_API_Interface $exchange_rate_api;

	protected Generate_Address_API_Interface $generate_address_api;

	protected Bitcoin_Wallet_Factory $bitcoin_wallet_factory;

	protected Bitcoin_Address_Factory $bitcoin_address_factory;

	public function __construct( Settings_Interface $settings, LoggerInterface $logger, Bitcoin_Wallet_Factory $bitcoin_wallet_factory, Bitcoin_Address_Factory $bitcoin_address_factory ) {
		$this->setLogger( $logger );
		$this->settings = $settings;

		$this->bitcoin_wallet_factory  = $bitcoin_wallet_factory;
		$this->bitcoin_address_factory = $bitcoin_address_factory;

		$this->bitcoin_api = new SoChain_API();

		$this->generate_address_api = new BitWasp_API( $logger );

		$this->exchange_rate_api = new Bitfinex_API( $logger );
	}


	/**
	 * Check a gateway id and determine is it an instance of this gateway type.
	 * Used on thank you page to return early.
	 *
	 * @used-by Thank_You::print_instructions()
	 *
	 * @param string $gateway_id
	 *
	 * @return bool
	 */
	public function is_bitcoin_gateway( string $gateway_id ): bool {

		$all_gateways = WC()->payment_gateways()->payment_gateways();

		return isset( $all_gateways[ $gateway_id ] ) && $all_gateways[ $gateway_id ] instanceof Bitcoin_Gateway;
	}


	/**
	 * Get all instances of the Bitcoin gateway.
	 * (typically there is only one).
	 *
	 * @return array<string, Bitcoin_Gateway>
	 */
	public function get_bitcoin_gateways(): array {
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
	 * @see https://github.com/BrianHenryIE/bh-wc-duplicate-payment-gateways
	 *
	 * @param int $order_id
	 *
	 * @return bool
	 */
	public function is_order_has_bitcoin_gateway( int $order_id ): bool {

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
	 */
	public function get_fresh_address_for_order( WC_Order $order ): Bitcoin_Address {

		$this->logger->debug( 'Get fresh address for `shop_order:' . $order->get_id() . '`' );

		$gateway_id = $order->get_payment_method();

		$gateway = $this->get_bitcoin_gateways()[ $gateway_id ];

		$xpub = $gateway->get_xpub();

		$wallet_post_id = $this->bitcoin_wallet_factory->get_post_id_for_wallet( $xpub );
		$wallet         = $this->bitcoin_wallet_factory->get_by_post_id( $wallet_post_id );

		$fresh_addresses = $wallet->get_fresh_addresses();

		// This mostly shouldn't happen, since it will be checked before the order is placed anyway.
		// A very unusual race condition could make it happen.
		if ( empty( $fresh_addresses ) ) {

			// TODO: This is inadequate. It only generates a new address, need to also check the address is unused.
			$generated_addresses = $this->generate_new_addresses_for_wallet( $gateway->get_xpub(), 1 );
			$fresh_addresses     = $generated_addresses['generated_addresses'];

		}

		if ( empty( $fresh_addresses ) ) {
			throw new Exception( 'No Bitcoin address available.' );
		}

		foreach ( $fresh_addresses as $address ) {
			$address_transactions = $this->query_api_for_address_transactions( $address );
			if ( ! empty( $address_transactions['transactions'] ) ) {
				if ( 'assigned' !== $address->get_status() ) {
					$address->set_status( 'used' );
				}
			} else {
				// Success, we have found an unused address.
				break;
			}
		}

		// But we maybe just got to the end of the array, so check again.
		if ( ! isset( $address ) || ! empty( $address_transactions['transactions'] ) ) {
			throw new Exception( 'No Bitcoin address available.' );
		}

		$raw_address = $address->get_raw_address();

		$address->set_order_id( $order->get_id() );
		$address->set_status( 'assigned' );

		$num_remaining_addresses = count( $fresh_addresses );

		$order->add_meta_data( Order::BITCOIN_ADDRESS_META_KEY, $raw_address, true );
		$order->save();

		// Schedule address generation if needed.
		if ( $num_remaining_addresses < 50 ) {
			$hook = Background_Jobs::GENERATE_NEW_ADDRESSES_HOOK;
			if ( ! as_has_scheduled_action( $hook ) ) {
				$this->logger->debug( "Under 50 addresses ($num_remaining_addresses) remaining, scheduling generate_new_addresses background job.", array( 'num_remaining_addresses' => $num_remaining_addresses ) );
				as_schedule_single_action( time(), $hook );
			}
		}

		$this->logger->debug(
			"Returning fresh address {$raw_address}. {$num_remaining_addresses} generated, fresh addresses remaining.",
			array(
				'address'                 => $address,
				'num_remaining_addresses' => $num_remaining_addresses,
			)
		);

		return $address;
	}

	/**
	 * Check do we have at least one address already generated and ready to use.
	 *
	 * @param Bitcoin_Gateway $gateway The gateway id the address is for.
	 *
	 * @return bool
	 */
	public function is_fresh_address_available_for_gateway( Bitcoin_Gateway $gateway ): bool {

		$xpub = $gateway->get_xpub();

		if ( empty( $xpub ) ) {
			return false;
		}

		$wallet_post_id = $this->bitcoin_wallet_factory->get_post_id_for_wallet( $xpub );

		if ( is_null( $wallet_post_id ) ) {
			$this->logger->error(
				'No post id for xpub: ' . $xpub,
				array(
					'gateway_id' => $gateway->id,
					'xpub'       => $xpub,
				)
			);
			return false;
		}

		$wallet          = $this->bitcoin_wallet_factory->get_by_post_id( $wallet_post_id );
		$fresh_addresses = $wallet->get_fresh_addresses();

		return count( $fresh_addresses ) > 0;
	}

	/**
	 * Get the current status of the order's payment.
	 *
	 * As a really detailed array for printing.
	 *
	 * @param WC_Order $order
	 * @param bool     $refresh
	 *
	 * @return array{btc_address:string, bitcoin_total:string, btc_price_at_at_order_time:string, transactions:array<string, TransactionArray>, btc_exchange_rate:string, last_checked_time:DateTimeInterface}
	 * @throws Exception
	 */
	public function get_order_details( WC_Order $order, bool $refresh = true ): array {

		// $currency    = $order_details['currency'];
		// $fiat_symbol = get_woocommerce_currency_symbol( $currency );
		//
		// TODO: Find a WooCommerce function which correctly places the currency symbol before/after.
		// $btc_price_at_at_order_time = $fiat_symbol . ' ' . $order_details['exchange_rate'];
		// $fiat_formatted_price       = $order->get_formatted_order_total();
		// $btc_price                  = $order_details['btc_price'];
		// $bitcoin_formatted_price    = $btc_symbol . wc_format_decimal( $btc_price, $round_btc );
		//
		// $btc_logo_url = $site_url . '/wp-content/plugins/bh-wc-bitcoin-gateway/assets/bitcoin.png';

		$result = array();

		$btc_xpub_address_string = $order->get_meta( Order::BITCOIN_ADDRESS_META_KEY );

		if ( empty( $btc_xpub_address_string ) ) {
			$this->logger->error( "`shop_order:{$order->get_id()} has no Bitcoin address.", array( 'order_id' => $order->get_id() ) );
			throw new Exception( 'Order has no Bitcoin address.' );
		}

		$order_id = $order->get_id();

		$result['order_id']            = $order_id;
		$result['order_status_before'] = $order->get_status();
		$result['btc_address']         = $btc_xpub_address_string;

		$result['btc_total'] = $order->get_meta( Order::ORDER_TOTAL_BITCOIN_AT_TIME_OF_PURCHASE_META_KEY, true );

		$result['btc_exchange_rate'] = $order->get_meta( Order::EXCHANGE_RATE_AT_TIME_OF_PURCHASE_META_KEY, true );

		$amount_received                      = $order->get_meta( Order::BITCOIN_AMOUNT_RECEIVED_META_KEY, true );
		$amount_received                      = is_numeric( $amount_received ) ? $amount_received : 0.0;
		$result['btc_amount_received']        = $amount_received;
		$result['btc_amount_received_before'] = $amount_received;

		$address_post_id                  = $this->bitcoin_address_factory->get_post_id_for_address( $btc_xpub_address_string );
		$address                          = $this->bitcoin_address_factory->get_by_post_id( $address_post_id );
		$result['bitcoin_address_object'] = $address;

		$order_date_created = $order->get_date_created();

		// Null if never checked before.
		$address_transactions = $address->get_transactions();

		// Filter transactions to this bitcoin address to only transactions that happened after the order was placed.
		$order_transactions_before = array_filter(
			$address_transactions ?? array(),
			function( array $transaction ) use ( $order_date_created ) {
				return $transaction['time'] > $order_date_created;
			}
		);

		if ( ! $refresh ) {
			$result['transactions'] = $order_transactions_before;
		}

		$bitcoin_gateways = $this->get_bitcoin_gateways();

		/** @var Bitcoin_Gateway $gateway */
		$gateway = $bitcoin_gateways[ $order->get_payment_method() ];

		// TODO: get from gateway.
		$gateway_num_required_confirmations = 3;

		if ( $refresh ) {

			$time_now = new DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );

			$updated_address = $this->query_api_for_address_transactions( $address );

			if ( $updated_address['updated'] ) {

				$refreshed_transactions = $updated_address['transactions'];

				// We're only concerned with transactions that happened after the order was placed.
				// TODO: Careful with timezones here.
				$order_transactions = array_filter(
					$refreshed_transactions,
					function( array $transaction ) use ( $order_date_created ) {
						return $transaction['time'] > $order_date_created;
					}
				);

				// Sum the transactions with the required number of confirmations.
				$result['btc_amount_received'] = array_reduce(
					$order_transactions,
					function( float $carry, array $transaction ) use ( $gateway_num_required_confirmations ): float {
						if ( $gateway_num_required_confirmations <= $transaction['confirmations'] ) {
							$carry += floatval( $transaction['value'] );
						}
						return $carry;
					},
					0.0
				);

				// Add a note saying "one new transactions seen, unconfirmed total =, confirmed total = ...".

				$note = '';
				if ( ! empty( $updated_address['updates']['new_transactions'] ) ) {
					// TODO: plural
					$note                  .= 'New transactions seen: ';
					$new_transactions_notes = array();
					foreach ( $updated_address['updates']['new_transactions'] as $new_transaction ) {
						$new_transactions_note    = '';
						$new_transactions_note   .= $new_transaction['txid']; // TODO: add href.
						$new_transactions_note   .= ', ' . $new_transaction['confirmations'] . ' confirmations';
						$new_transactions_notes[] = $new_transactions_note;
					}
					$note .= implode( ',', $new_transactions_notes ) . ".\n\n";
				}

				if ( ! empty( $updated_address['updates']['new_confirmations'] ) ) {
					$above_required_confirmations = array_reduce(
						$updated_address['updates']['new_confirmations'],
						function( bool $carry, array $transaction ) use ( $gateway_num_required_confirmations ) {
							return $carry && $transaction['confirmations'] >= $gateway_num_required_confirmations;
						},
						true
					);
					foreach ( $updated_address['updates']['new_confirmations'] as $transaction ) {
						if ( $above_required_confirmations ) {
							$note .= 'Transaction ';
							$note .= $transaction['txid']; // TODO: add href.
							$note .= ' now has ' . $transaction['confirmations'] . ".\n\n";
						}
					}
				}

				if ( ! empty( $note ) ) {
					$this->logger->info(
						$note,
						array(
							'order_id' => $order_id,
							'updates'  => $updated_address['updates'],
						)
					);

					$order->add_order_note( $note );
				}
			}

			// Maybe mark the order as paid.
			if ( ! $order->is_paid() ) {
				$expected     = $result['btc_total'];
				$price_margin = $gateway->get_price_margin_percent();

				$minimum_payment = $expected * ( 100 - $price_margin ) / 100;

				if ( $result['btc_amount_received'] > $minimum_payment ) {
					$order->payment_complete( $btc_xpub_address_string );
					$this->logger->info( "`shop_order:{$order_id}` has been marked paid.", array( 'order_id' => $order_id ) );
				}
			}

			// @phpstan-ignore-next-line This works fine.
			$order->add_meta_data( Order::LAST_CHECKED_META_KEY, $time_now, true );
			$order->save();

			$last_checked_time = $time_now;
		} else {
			$last_checked_time = $order->get_meta( Order::LAST_CHECKED_META_KEY );
			$last_checked_time = empty( $last_checked_time ) ? null : $last_checked_time;
		}

		$result['last_checked_time'] = $last_checked_time;

		$result['order_status'] = $order->get_status();

		$result['order'] = $order;

		// If the order is not marked paid, but has transactions, it is partly-paid.
		switch ( true ) {
			case $order->is_paid():
				$result['status'] = __( 'Paid', 'bh-wc-bitcoin-gateway' );
				break;
			case ! empty( $refreshed_transactions ):
				$result['status'] = __( 'Partly Paid', 'bh-wc-bitcoin-gateway' );
				break;
			default:
				$result['status'] = __( 'Awaiting Payment', 'bh-wc-bitcoin-gateway' );
		}

		return $result;
	}

	/**
	 * @param WC_Order $order
	 * @param bool     $refresh
	 *
	 * @return array{btc_total_formatted:string, btc_exchange_rate_formatted:string, order_status_before_formatted:string, order_status_formatted:string, btc_amount_received_formatted:string, last_checked_time_formatted:string}
	 * @throws Exception
	 */
	public function get_formatted_order_details( WC_Order $order, bool $refresh = true ): array {

		$result = array();

		$order_details = $this->get_order_details( $order, $refresh );

		// ฿ U+0E3F THAI CURRENCY SYMBOL BAHT, decimal: 3647, HTML: &#3647;, UTF-8: 0xE0 0xB8 0xBF, block: Thai.
		$btc_symbol                    = '฿';
		$result['btc_total_formatted'] = $btc_symbol . ' ' . wc_trim_zeros( $order_details['btc_total'] );

		$result['btc_exchange_rate_formatted'] = wc_price( $order_details['btc_exchange_rate'], array( 'currency' => $order->get_currency() ) );

		$result['order_status_before_formatted'] = wc_get_order_statuses()[ 'wc-' . $order_details['order_status_before'] ];

		$result['order_status_formatted'] = wc_get_order_statuses()[ 'wc-' . $order_details['order_status'] ];

		$result['btc_amount_received_formatted'] = $btc_symbol . ' ' . $order_details['btc_amount_received'];

		if ( isset( $order_details['last_checked_time'] ) ) {
			$last_checked_time = $order_details['last_checked_time'];
			$date_format       = get_option( 'date_format' );
			$time_format       = get_option( 'time_format' );
			$timezone          = wp_timezone_string();

			// $last_checked_time is in UTC... change it to local time.?
			// The server time is not local time... maybe use their address?
			// @see https://stackoverflow.com/tags/timezone/info

			$result['last_checked_time_formatted'] = $last_checked_time->format( $date_format . ', ' . $time_format ) . ' ' . $timezone;
		} else {
			$result['last_checked_time_formatted'] = 'Never';
		}

		/** @var Bitcoin_Address $address */
		$address = $order_details['bitcoin_address_object'];

		$result['btc_address_derivation_path_sequence_number'] = $address->get_derivation_path_sequence_number();

		$wallet                = $this->bitcoin_wallet_factory->get_by_post_id( $address->get_wallet_parent_post_id() );
		$xpub                  = $wallet->get_xpub();
		$xpub_friendly_display = substr( $xpub, 0, 7 ) . ' ... ' . substr( $xpub, -3, 3 );
		$xpub_js_span          = "<span style=\"border-bottom: 1px dashed #999; word-wrap: break-word\" onclick=\"this.innerText = this.innerText === '{$xpub}' ? '{$xpub_friendly_display}' : '{$xpub}';\" title=\"{$xpub}\"'>{$xpub_friendly_display}</span>";

		$result['parent_wallet_xpub_html'] = $xpub_js_span;

		// TODO: Link to the CPT list table.
		// $result['parent_wallet_url'] =

		// Add a link showing the exchange rate around the time of the order ( -12 hours to +12 hours after payment).

		/**
		 * This supposedly could be null, but I can't imagine a scenario where WooCommerce returns an order object
		 * that doesn't have a DateTime for created.
		 *
		 * @var DateTime $date_created
		 */
		$date_created = $order->get_date_created();
		$from         = $date_created->getTimestamp() - ( DAY_IN_SECONDS / 2 );
		if ( ! is_null( $order->get_date_paid() ) ) {
			$to = $order->get_date_paid()->getTimestamp() + ( DAY_IN_SECONDS / 2 );
		} else {
			$to = $from + DAY_IN_SECONDS;
		}
		$exchange_rate_url                  = "https://www.blockchain.com/prices/BTC?from={$from}&to={$to}&timeSpan=custom&scale=0&style=line";
		$order_details['exchange_rate_url'] = $exchange_rate_url;

		// Unchanged data.
		foreach ( array( 'order', 'btc_total', 'btc_exchange_rate', 'btc_address', 'transactions', 'btc_amount_received', 'status' ) as $key ) {
			$result[ $key ] = $order_details[ $key ];
		}

		return $result;
	}

	/**
	 * Return the cached exchange rate, or fetch it.
	 * Cache for one hour.
	 *
	 * Value of 1 BTC.
	 *
	 * @return string
	 */
	public function get_exchange_rate( string $currency ): string {
		$transient_name = 'bh_wc_bitcoin_gateway_exchange_rate_' . $currency;

		$exchange_rate = get_transient( $transient_name );

		if ( empty( $exchange_rate ) ) {
			$exchange_rate = $this->exchange_rate_api->get_exchange_rate( $currency );
			set_transient( $transient_name, $exchange_rate, HOUR_IN_SECONDS );
		}

		return $exchange_rate;
	}

	/**
	 * @param string $currency 'USD'|'EUR'|'GBP'.
	 * @param float  $fiat_amount This is stored in the WC_Order object as a float.
	 *
	 * @return string Bitcoin amount.
	 */
	public function convert_fiat_to_btc( string $currency, float $fiat_amount ): string {

		// 1 BTC = xx USD
		$exchange_rate = $this->get_exchange_rate( $currency );

		$float_result = $fiat_amount / floatval( $exchange_rate );

		return (string) $float_result;
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
	 * @return array{wallet: Bitcoin_Wallet, wallet_post_id: int, existing_fresh_addresses:array, generated_addresses:array}
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

			$check_new_addresses_result = $this->check_new_addresses_for_transactions( $generated_addresses );

		}

		$result['existing_fresh_addresses'] = $existing_fresh_addresses;

		// TODO: Only return / distinguish which generated addresses are fresh.
		$result['generated_addresses'] = $generated_addresses;

		$result['wallet_post_id'] = $post_id;

		return $result;
	}


	/**
	 * If a wallet has fewer than 50 fresh addresses available, generate some more.
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
					$result[ $gateway->id ]['wallet_post_id'] = $this->bitcoin_wallet_factory->save_new( $wallet_address, $gateway->id );
				} catch ( Exception $exception ) {
					$this->logger->error( 'Failed to save new wallet.' );
					continue;
				}
			} else {
				$result[ $gateway->id ]['wallet_post_id'] = $wallet_post_id;
			}

			$wallet = $this->bitcoin_wallet_factory->get_by_post_id( $wallet_post_id );

			$fresh_addresses = $wallet->get_fresh_addresses();

			if ( count( $fresh_addresses ) > 50 ) {
				continue;
			}

			$generated_addresses_result = $this->generate_new_addresses_for_wallet( $gateway->get_xpub() );

			$generated_addresses = $generated_addresses_result['generated_addresses'];

			$result[ $gateway->id ]['new_addresses'] = $generated_addresses;

			$this->check_new_addresses_for_transactions( $generated_addresses );

		}

		return $result;
	}


	/**
	 * @param string $xpub
	 * @param int    $generate_count // TODO: Change this up to 50? when in prod.
	 *
	 * @return array{xpub:string, generated_addresses:array<Bitcoin_Address>, generated_addresses_count:int, generated_addresses_post_ids:array<int>, address_index:int}
	 *
	 * @throws Exception
	 */
	public function generate_new_addresses_for_wallet( string $xpub, int $generate_count = 25 ): array {

		$result = array();

		$result['xpub'] = $xpub;

		$wallet_post_id = $this->bitcoin_wallet_factory->get_post_id_for_wallet( $xpub );

		if ( is_null( $wallet_post_id ) ) {
			throw new \Exception();
		}

		$wallet = $this->bitcoin_wallet_factory->get_by_post_id( $wallet_post_id );

		$address_index = $wallet->get_address_index();

		$generated_addresses_post_ids = array();
		$generated_addresses_count    = 0;

		do {

			// TODO: Post increment or we will never generate address 0 like this.
			$address_index++;

			$new_address = $this->generate_address_api->generate_address( $xpub, $address_index );

			if ( ! is_null( $this->bitcoin_address_factory->get_post_id_for_address( $new_address ) ) ) {
				continue;
			}

			$bitcoin_address_new_post_id = $this->bitcoin_address_factory->save_new( $new_address, $address_index, $wallet );

			$generated_addresses_post_ids[] = $bitcoin_address_new_post_id;
			$generated_addresses_count++;

		} while ( $generated_addresses_count < $generate_count );

		$result['generated_addresses_count']    = $generated_addresses_count;
		$result['generated_addresses_post_ids'] = $generated_addresses_post_ids;
		$result['generated_addresses']          = array_map(
			function( int $post_id ): Bitcoin_Address {
				return $this->bitcoin_address_factory->get_by_post_id( $post_id );
			},
			$generated_addresses_post_ids
		);
		$result['address_index']                = $address_index;

		$wallet->set_address_index( $address_index );

		if ( $generate_count > 0 ) {
			// Check the new addresses for transactions etc.
			// TODO: Refactor for testing. Schedule?
			$this->check_new_addresses_for_transactions();
		}

		return $result;
	}

	/**
	 * @used-by Background_Jobs::check_new_addresses_for_transactions()
	 *
	 * @param Bitcoin_Address[] $addresses
	 *
	 * @return array<string, array{address:Bitcoin_Address, transactions:array<string, TransactionArray>, updated:bool, updates:array{new_transactions:array<string, TransactionArray>, new_confirmations:array<string, TransactionArray>}, previous_transactions:array<string, TransactionArray>|null}>
	 */
	public function check_new_addresses_for_transactions( ?array $addresses = null ): array {

		$result = array();

		if ( is_null( $addresses ) ) {

			$addresses = array();

			// Get all wallets whose status is unknown.
			$posts = get_posts(
				array(
					'post_type'      => Bitcoin_Address::POST_TYPE,
					'post_status'    => 'unknown',
					'posts_per_page' => 200,
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
		}
		try {
			foreach ( $addresses as $bitcoin_address ) {

				// Check for updates.
				$result[ $bitcoin_address->get_raw_address() ] = $this->query_api_for_address_transactions( $bitcoin_address );
			}
		} catch ( \Exception $exception ) {
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
		// are already used. => We really need to generate new addresses until we have some.

		// TODO: Return something useful.
		return $result;
	}

	/**
	 * Remotely check/fetch the latest data for an address.
	 *
	 * @param Bitcoin_Address $address
	 *
	 * @return array{address:Bitcoin_Address, transactions:array<string, TransactionArray>, updated:bool, updates:array{new_transactions:array<string, TransactionArray>, new_confirmations:array<string, TransactionArray>}, previous_transactions:array<string, TransactionArray>|null}
	 * @throws Exception
	 */
	public function query_api_for_address_transactions( Bitcoin_Address $address ): array {

		$btc_xpub_address_string = $address->get_raw_address();

		// Null when never checked before.
		$previous_transactions = $address->get_transactions();

		$refreshed_transactions = $this->bitcoin_api->get_transactions_received( $btc_xpub_address_string );

		$updates                      = array();
		$updates['new_transactions']  = array();
		$updates['new_confirmations'] = array();

		if ( is_null( $previous_transactions ) ) {
			$updates['new_transactions'] = $refreshed_transactions;

		} else {
			foreach ( $refreshed_transactions as $txid => $refreshed_transaction ) {
				if ( ! isset( $previous_transactions[ $txid ] ) ) {
					$updates['new_transactions'][ $txid ] = $refreshed_transaction;
				} elseif ( $previous_transactions[ $txid ]['confirmations'] !== $refreshed_transaction['confirmations'] ) {
					$updates['new_confirmations'][ $txid ] = $refreshed_transaction;
				}
			}
		}

		$updated = is_null( $previous_transactions ) || ! empty( $updates['new_transactions'] ) || ! empty( $updates['new_confirmations'] );

		if ( $updated ) {
			$address->set_transactions( $refreshed_transactions );
		}

		return array(
			'address'               => $address,
			'transactions'          => $refreshed_transactions,
			'updated'               => $updated,
			'updates'               => $updates,
			'previous_transactions' => $previous_transactions,
		);

	}

}
