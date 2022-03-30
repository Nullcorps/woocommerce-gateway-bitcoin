<?php
/**
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

namespace Nullcorps\WC_Gateway_Bitcoin\API\Bitcoin;

interface Blockchain_API_Interface {

	/**
	 * The total amount in BTC received at this address.
	 *
	 * @param string $btc_address The Bitcoin address.
	 * @param bool   $confirmed
	 *
	 * @return float
	 */
	public function get_received_by_address( string $btc_address, bool $confirmed ): float;

	/**
	 * The current balance of the address.
	 *
	 * @param string $btc_address
	 * @param bool   $confirmed
	 *
	 * @return float
	 */
	public function get_address_balance( string $btc_address, bool $confirmed ): float;

	/**
	 * @param string $btc_address
	 *
	 * @return array<array{txid:string, time:string, value:float}>
	 */
	public function get_transactions( string $btc_address ): array;

}
