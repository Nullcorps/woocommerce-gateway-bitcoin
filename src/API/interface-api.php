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
	 * @param string $currency
	 *
	 * @return float
	 */
	public function get_exchange_rate( string $currency ): float;

	/**
	 * @used-by WC_Gateway_Bitcoin::process_payment()
	 *
	 * @param string $currency
	 * @param float  $fiat_amount
	 *
	 * @return float
	 */
	public function convert_fiat_to_btc( string $currency, float $fiat_amount ): float;


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
	 * @param WC_Order $order
	 * @param bool     $refresh Query remote APIs to refresh the details, or just return cached data.
	 *
	 * @return array{btc_address:string, bitcoin_total:string, btc_price_at_at_order_time:string}
	 */
	public function get_order_details( WC_Order $order, bool $refresh = true ): array;

	/**
	 * @return array<string, array{gateway_id:string, xpub:string, new_addresses_count:int, new_addresses:array<string>, address_index:int}>
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
