<?php
/**
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

namespace Nullcorps\WC_Gateway_Bitcoin\API;

use Exception;
use Nullcorps\WC_Gateway_Bitcoin\API\Address_Storage\Crypto_Address;
use Nullcorps\WC_Gateway_Bitcoin\WooCommerce\WC_Gateway_Bitcoin;
use WC_Order;


/**
 * @phpstan-type TransactionArray array{txid:string, time:\DateTimeInterface, value:string, confirmations:int}
 */
interface API_Interface {

	/**
	 * Given an order id, determine is the order's gateway an instance of this Bitcoin gateway.
	 *
	 * @see https://github.com/BrianHenryIE/bh-wc-duplicate-payment-gateways
	 *
	 * @param int $order_id
	 *
	 * @return bool
	 */
	public function is_order_has_bitcoin_gateway( int $order_id ): bool;

	/**
	 * Given a gateway id as a string, determine is it an instance of this Bitcoin gateway.
	 *
	 * @see https://github.com/BrianHenryIE/bh-wc-duplicate-payment-gateways
	 *
	 * @param string $gateway_id
	 *
	 * @return bool
	 */
	public function is_bitcoin_gateway( string $gateway_id ): bool;

	/**
	 * @return WC_Gateway_Bitcoin[]
	 */
	public function get_bitcoin_gateways(): array;

	/**
	 *
	 * @used-by WC_Gateway_Bitcoin::process_payment()
	 *
	 * @param string $currency USD|EUR|GBP.
	 *
	 * @return string
	 */
	public function get_exchange_rate( string $currency ): string;

	/**
	 * Get the Bitcoin value of a local currency amount.
	 *
	 * @used-by WC_Gateway_Bitcoin::process_payment()
	 *
	 * @param string $currency From which currency (USD|EUR|GBP).
	 * @param string $fiat_amount The amount to convert.
	 *
	 * @return string
	 */
	public function convert_fiat_to_btc( string $currency, float $fiat_amount ): string;

	/**
	 * Return an unused address for use in an order.
	 * Adds the address as metadata to the order.
	 *
	 * @param WC_Order $order The (newly placed) WooCommerce order.
	 *
	 * @return Crypto_Address
	 * @throws Exception When no address is available.
	 */
	public function get_fresh_address_for_order( WC_Order $order ): Crypto_Address;

	/**
	 * Return the current Bitcoin details for an order, optionally refresh.
	 *
	 * @param WC_Order $order   WooCommerce order object.
	 * @param bool     $refresh Query remote APIs to refresh the details, or just return cached data.
	 *
	 * @return array{btc_address:string, bitcoin_total:string, btc_total_formatted:string, btc_price_at_at_order_time:string, last_checked_time_formatted:string, btc_amount_received_formatted:string, transactions:array, btc_exchange_rate:string}
	 */
	public function get_order_details( WC_Order $order, bool $refresh = true ): array;

	/**
	 * @param WC_Order $order
	 * @param bool     $refresh
	 *
	 * @return array{btc_total_formatted:string, btc_exchange_rate_formatted:string, order_status_before_formatted:string, order_status_formatted:string, btc_amount_received_formatted:string, last_checked_time_formatted:string}
	 * @throws Exception
	 */
	public function get_formatted_order_details( WC_Order $order, bool $refresh = true ): array;

	/**
	 * @return array<string, array{}|array{wallet_post_id:int, new_addresses: array{gateway_id:string, xpub:string, generated_addresses:array<Crypto_Address>, generated_addresses_count:int, generated_addresses_post_ids:array<int>, address_index:int}}>
	 */
	public function generate_new_addresses(): array;

	public function update_address( Crypto_Address $address ): array;

	/**
	 * Determine do we have any fresh address available for this gateway.
	 * Used so the gateway is not displayed at checkout if there are no addresses ready.
	 *
	 * @used-by WC_Gateway_Bitcoin::is_available()
	 *
	 * @param string $gateway_id
	 *
	 * @return bool
	 */
	public function is_fresh_address_available_for_gateway( string $gateway_id ): bool;
}
