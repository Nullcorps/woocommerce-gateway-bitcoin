<?php
/**
 *
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

namespace Nullcorps\WC_Gateway_Bitcoin\API;

use DateTimeImmutable;
use DateTimeInterface;
use Nullcorps\WC_Gateway_Bitcoin\Action_Scheduler\Background_Jobs;
use Nullcorps\WC_Gateway_Bitcoin\API\Bitcoin\Bitfinex_API;
use Nullcorps\WC_Gateway_Bitcoin\API\Bitcoin\BitWasp_API;
use Nullcorps\WC_Gateway_Bitcoin\API\Bitcoin\Blockchain_Info_API;
use Nullcorps\WC_Gateway_Bitcoin\API\Bitcoin\Blockstream_Info_API;
use Nullcorps\WC_Gateway_Bitcoin\API\Bitcoin\Blockchain_API_Interface;
use Nullcorps\WC_Gateway_Bitcoin\API\Bitcoin\Exchange_Rate_API_Interface;
use Nullcorps\WC_Gateway_Bitcoin\API\Bitcoin\Generate_Address_API_Interface;
use Nullcorps\WC_Gateway_Bitcoin\WooCommerce\Order;
use Nullcorps\WC_Gateway_Bitcoin\WooCommerce\Thank_You;
use Nullcorps\WC_Gateway_Bitcoin\WooCommerce\WC_Gateway_Bitcoin;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Order;
use WC_Payment_Gateway;
use WC_Payment_Gateways;

class API implements API_Interface {
	use LoggerAwareTrait;

	protected Settings_Interface $settings;

	protected Blockchain_API_Interface $bitcoin_api;

	protected Exchange_Rate_API_Interface $exchange_rate_api;

	protected Address_Storage $address_storage;

	protected Generate_Address_API_Interface $generate_address_api;

	public function __construct( Settings_Interface $settings, LoggerInterface $logger ) {
		$this->setLogger( $logger );
		$this->settings = $settings;

		if ( 'Blockchain.info' === $settings->get_api_preference() ) {
			$this->bitcoin_api = new Blockchain_Info_API( $logger );
		} else {
			$this->bitcoin_api = new Blockstream_Info_API( $logger );
		}

		$this->address_storage      = new Address_Storage( $settings, $logger );
		$this->generate_address_api = new BitWasp_API( $this->address_storage, $logger );

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

		return isset( $all_gateways[ $gateway_id ] ) && $all_gateways[ $gateway_id ] instanceof WC_Gateway_Bitcoin;
	}


	/**
	 * Get all instances of the Bitcoin gateway.
	 * (typically there is only one).
	 *
	 * @return array<string, WC_Gateway_Bitcoin>
	 */
	public function get_bitcoin_gateways(): array {
		$payment_gateways = WC_Payment_Gateways::instance()->payment_gateways();
		$bitcoin_gateways = array();
		foreach ( $payment_gateways as $gateway ) {
			if ( $gateway instanceof WC_Gateway_Bitcoin ) {
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
	 * @used-by WC_Gateway_Bitcoin::process_payment()
	 *
	 * @param WC_Order $order The order that will use the address.
	 *
	 * @return string
	 */
	public function get_fresh_address_for_order( WC_Order $order ): string {

		$this->logger->debug( 'Get fresh address for order ' . $order->get_id() );

		$gateway_id = $order->get_payment_method();

		$fresh_addresses = $this->address_storage->get_fresh_address_list( $gateway_id );

		// This mostly shouldn't happen, since it will be checked before the order is placed anyway.
		// A very unusual race condition could make it happen.
		if ( empty( $fresh_addresses ) ) {
			$bitcoin_gateways = $this->get_bitcoin_gateways();
			$gateway          = $bitcoin_gateways[ $gateway_id ];
			$this->generate_new_addresses_for_gateway( $gateway, 1 );
			$fresh_addresses = $this->address_storage->get_fresh_address_list( $gateway_id );
			if ( empty( $fresh_addresses ) ) {
				throw new \Exception( 'No Bitcoin address available.' );
			}
		}

		$address = array_pop( $fresh_addresses );

		// Check with remote API that it has not been used.
		while ( 0.0 !== $this->bitcoin_api->get_received_by_address( $address, false ) ) {

			$this->address_storage->save_used_address( $gateway_id, $address );

			if ( ! empty( $fresh_addresses ) ) {
				$address = array_pop( $fresh_addresses );
			} else {
				throw new \Exception( 'No Bitcoin address available.' );
			}
		}

		$this->address_storage->save_fresh_address_list( $gateway_id, $fresh_addresses );

		$num_remaining_addresses = count( $fresh_addresses );

		$this->logger->debug(
			"Removing and returning fresh address {$address}. {$num_remaining_addresses} remaining.",
			array(
				'address'                 => $address,
				'num_remaining_addresses' => $num_remaining_addresses,
			)
		);

		$this->address_storage->save_used_address( $gateway_id, $address );

		$order->add_meta_data( Order::BITCOIN_ADDRESS_META_KEY, $address, true );
		$order->save();

		// Schedule address generation if needed.
		if ( $num_remaining_addresses < 50 ) {
			$hook = Background_Jobs::GENERATE_NEW_ADDRESSES_HOOK;
			if ( ! as_has_scheduled_action( $hook ) ) {
				as_schedule_single_action( time(), $hook );
			}
		}

		return $address;
	}

	/**
	 * Check do we have at least one address already generated and ready to use.
	 *
	 * @param string $gateway_id The gateway id the address is for.
	 *
	 * @return bool
	 */
	public function is_fresh_address_available_for_gateway( string $gateway_id ): bool {
		return count( $this->address_storage->get_fresh_address_list( $gateway_id ) ) > 0;
	}

	/**
	 *
	 * TODO
	 *
	 * @param WC_Order $order
	 * @param bool     $refresh
	 *
	 * @return array
	 */
	public function get_order_details( WC_Order $order, bool $refresh = true ): array {

		//
		// $currency    = $order_details['currency'];
		// $fiat_symbol = get_woocommerce_currency_symbol( $currency );
		//
		// TODO: Find a WooCommerce function which correctly places the currency symbol before/after.
		// $btc_price_at_at_order_time = $fiat_symbol . ' ' . $order_details['exchange_rate'];
		// $fiat_formatted_price       = $order->get_formatted_order_total();
		// $btc_price                  = $order_details['btc_price'];
		// $bitcoin_formatted_price    = $btc_symbol . wc_format_decimal( $btc_price, $round_btc );
		//
		// $btc_logo_url = $site_url . '/wp-content/plugins/nullcorps-woocommerce-gateway-bitcoin/assets/bitcoin.png';

		$result = array();

		$btc_address = $order->get_meta( Order::BITCOIN_ADDRESS_META_KEY );

		if ( empty( $btc_address ) ) {
			throw new \Exception( 'Order has no Bitcoin address.' );
		}

		$order_id = $order->get_id();

		$result['order_id']               = $order_id;
		$result['order_status']           = $order->get_status();
		$result['order_status_formatted'] = wc_get_order_statuses()[ 'wc-' . $order->get_status() ];
		$result['order']                  = $order;
		$result['btc_address']            = $btc_address;

		$result['btc_total'] = $order->get_meta( Order::ORDER_TOTAL_BITCOIN_AT_TIME_OF_PURCHASE_META_KEY, true );
		// ฿ U+0E3F THAI CURRENCY SYMBOL BAHT, decimal: 3647, HTML: &#3647;, UTF-8: 0xE0 0xB8 0xBF, block: Thai.
		$btc_symbol                    = '฿';
		$result['btc_total_formatted'] = $btc_symbol . ' ' . $result['btc_total'];

		$result['btc_exchange_rate']           = $order->get_meta( Order::EXCHANGE_RATE_AT_TIME_OF_PURCHASE_META_KEY, true );
		$result['btc_exchange_rate_formatted'] = get_woocommerce_currency_symbol( $order->get_currency() ) . $result['btc_exchange_rate'];

		$amount_received               = $order->get_meta( Order::BITCOIN_AMOUNT_RECEIVED_META_KEY, true );
		$amount_received               = is_numeric( $amount_received ) ? $amount_received : 0.0;
		$result['btc_amount_received'] = $amount_received;

		/** @var array<array{txid:string, time:string, value:float}> $previous_transactions */
		$previous_transactions  = $order->get_meta( Order::TRANSACTIONS_META_KEY );
		$previous_transactions  = is_array( $previous_transactions ) ? $previous_transactions : array();
		$result['transactions'] = array();
		foreach ( $previous_transactions as $transaction ) {
			$result['transactions'][ $transaction['txid'] ] = $transaction;
		}

		// TOOD: This is basically what the saved order data is before refreshing.
		$this->logger->debug(
			count( $previous_transactions ) . ' previously saved transactions for order ' . $order_id,
			array(
				'previous_transactions' => $previous_transactions,
				'order_id'              => $order_id,
				'result'                => $result,
			)
		);

		if ( $refresh ) {

			$time_now = new DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );

			// Check has it been paid.
			$address_balance = $this->bitcoin_api->get_address_balance( $btc_address, true );

			// There must be a new transaction...
			if ( $address_balance !== $amount_received ) {

				$result['btc_amount_received'] = $address_balance;

				$refreshed_transactions = $this->bitcoin_api->get_transactions( $btc_address );

				foreach ( $refreshed_transactions as $transaction ) {
					if ( ! isset( $result['transactions'][ $transaction['txid'] ] ) ) {
						$transaction['datetime_first_seen']             = $time_now;
						$result['transactions'][ $transaction['txid'] ] = $transaction;
						$order->add_meta_data( Order::TRANSACTIONS_META_KEY, $result['transactions'], true );

						$log_message = "New payment of {$transaction['value']} seen in transaction {$transaction['txid']}.";
						$note        = "New payment of {$transaction['value']} seen in transaction <a target=\"_blank\" href=\"https://blockchain.info/rawaddr/{$transaction['txid']}\">{$transaction['txid']}</a>.";

						$this->logger->info(
							$log_message,
							array(
								'transaction' => $transaction,
								'order_id'    => $order_id,
							)
						);

						$order->add_order_note( $note );
					}
				}

				$expected = $result['btc_total'];

				$bitcoin_gateways = $this->get_bitcoin_gateways();

				/** @var WC_Gateway_Bitcoin $gateway */
				$gateway = $bitcoin_gateways[ $order->get_payment_method() ];

				$price_margin = $gateway->get_price_margin();

				$minimum_payment = $expected * ( 100 - $price_margin ) / 100;

				if ( $address_balance > $minimum_payment && ! $order->is_paid() ) {
					$order->payment_complete( $btc_address );
					$this->logger->info( "Order {$order_id} has been marked paid.", array( 'order_id' => $order_id ) );
				}
			}

			$order->add_meta_data( Order::LAST_CHECKED_META_KEY, $time_now, true );
			$order->save();

		}

		$result['btc_amount_received_formatted'] = $btc_symbol . ' ' . $result['btc_amount_received'];

		$last_checked_time           = $order->get_meta( Order::LAST_CHECKED_META_KEY );
		$last_checked_time           = empty( $last_checked_time ) ? null : $last_checked_time;
		$result['last_checked_time'] = $last_checked_time;

		if ( $last_checked_time instanceof DateTimeInterface ) {
			$date_format = get_option( 'date_format' );
			$time_format = get_option( 'time_format' );
			$timezone    = wp_timezone_string();

			// $last_checked_time is in UTC... change it to local time.?
			// The server time is not local time... maybe use their address?
			// @see https://stackoverflow.com/tags/timezone/info

			$result['last_checked_time_formatted'] = $last_checked_time->format( $date_format . ', ' . $time_format ) . ' ' . $timezone;
		} else {
			$result['last_checked_time_formatted'] = 'Never';
		}

		// If the order is not marked paid, but has transactions, it is partly-paid.
		switch ( true ) {
			case $order->is_paid():
				$result['status'] = __( 'Paid', 'nullcorps-wc-gateway-bitcoin' );
				break;
			case ! empty( $refreshed_transactions ):
				$result['status'] = __( 'Partly Paid', 'nullcorps-wc-gateway-bitcoin' );
				break;
			default:
				$result['status'] = __( 'Awaiting Payment', 'nullcorps-wc-gateway-bitcoin' );
		}

		return $result;
	}


	/**
	 * Return the cached exchange rate, or fetch it.
	 * Cache for one hour.
	 *
	 * Value of 1 BTC.
	 *
	 * @return float
	 */
	public function get_exchange_rate( string $currency ): float {
		$transient_name = 'woobtc_exchange_rate_' . $currency;

		$exchange_rate = get_transient( $transient_name );

		if ( empty( $exchange_rate ) ) {
			$exchange_rate = $this->exchange_rate_api->get_exchange_rate( $currency );
			set_transient( $transient_name, $exchange_rate, HOUR_IN_SECONDS );
		}

		return floatval( $exchange_rate );
	}

	/**
	 * @param string $currency 'USD'|'EUR'|'GBP'.
	 * @param float  $fiat_amount
	 *
	 * @return float Bitcoin amount.
	 */
	public function convert_fiat_to_btc( string $currency, float $fiat_amount ): float {

		// 1 BTC = xx USD
		$exchange_rate = $this->get_exchange_rate( $currency );

		return $fiat_amount / $exchange_rate;
	}


	/**
	 * @return array<string, array{gateway_id:string, xpub:string, new_addresses_count:int, new_addresses:array<string>, address_index:int}>
	 */
	public function generate_new_addresses(): array {

		$result = array();

		foreach ( $this->get_bitcoin_gateways() as $gateway ) {
			$result[ $gateway->id ] = $this->generate_new_addresses_for_gateway( $gateway );
		}

		return $result;
	}


	/**
	 * @param WC_Gateway_Bitcoin $gateway
	 * @param int                $generate_count
	 *
	 * @return array{gateway_id:string, xpub:string, new_addresses_count:int, new_addresses:array<string>, address_index:int}
	 * @throws \Nullcorps\WC_Gateway_Bitcoin\BitWasp\Bitcoin\Exceptions\DisallowedScriptDataFactoryException
	 * @throws \Nullcorps\WC_Gateway_Bitcoin\BitWasp\Bitcoin\Exceptions\InvalidNetworkParameter
	 * @throws \Nullcorps\WC_Gateway_Bitcoin\BitWasp\Bitcoin\Exceptions\RandomBytesFailure
	 * @throws \Nullcorps\WC_Gateway_Bitcoin\BitWasp\Buffertools\Exceptions\ParserOutOfRange
	 * @throws \Exception
	 * @throws \Nullcorps\WC_Gateway_Bitcoin\BitWasp\Bitcoin\Exceptions\Base58ChecksumFailure
	 */
	protected function generate_new_addresses_for_gateway( WC_Gateway_Bitcoin $gateway, int $generate_count = 50 ): array {

		$result = array();

		$gateway_id = $gateway->id;
		$xpub       = $gateway->get_xpub();

		$result['gateway_id'] = $gateway_id;
		$result['xpub']       = $xpub;

		$fresh_address_list = $this->address_storage->get_fresh_address_list( $gateway_id );

		$address_index = count( $this->address_storage->get_used_address_list( $gateway_id ) ) + count( $fresh_address_list );

		$new_addresses       = array();
		$new_addresses_count = 0;

		// TODO: Catch exceptions, e.g. 429, and save what was achieved.
		do {

			$new_address = $this->generate_address_api->generate_address( $xpub, $address_index );

			$received_by_address = $this->bitcoin_api->get_received_by_address( $new_address, false );

			if ( 0.0 === $received_by_address ) {
				$new_addresses[] = $new_address;
				$new_addresses_count++;
			}

			$address_index++;

		} while ( $new_addresses_count < $generate_count );

		$result['new_addresses_count'] = $new_addresses_count;
		$result['new_addresses']       = $new_addresses;
		$result['address_index']       = $address_index;

		// TODO: Save address_index in gateway meta.

		$fresh_address_list = array_merge( $fresh_address_list, $new_addresses );

		$this->address_storage->save_fresh_address_list( $gateway_id, $fresh_address_list );

		return $result;
	}

	/**
	 * Remotely check/fetch the latest data for an address.
	 *
	 * @param Crypto_Address $address
	 *
	 * @return array{address:Crypto_Address, transactions:array<string, TransactionArray>, updated:bool, updates:array{new_transactions:array<string, TransactionArray>, new_confirmations:array<string, TransactionArray>}, previous_transactions:array<string, TransactionArray>|null}
	 * @throws Exception
	 */
	public function update_address( Crypto_Address $address ): array {

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
