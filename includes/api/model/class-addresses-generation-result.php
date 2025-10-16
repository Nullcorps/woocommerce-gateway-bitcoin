<?php
/**
 * Model class representing the result of generating new addresses for all gateways.
 *
 * Replaces: array{}|array{wallet_post_id:int, new_addresses: array{gateway_id:string, xpub:string, generated_addresses:array<Bitcoin_Address>, generated_addresses_count:int, generated_addresses_post_ids:array<int>, address_index:int}}
 *
 * @package brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\API\Model;

use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Wallet;

/**
 * Addresses generation result model.
 *
 * @see API::generate_new_addresses()
 * @see API_Interface::generate_new_addresses()
 */
class Addresses_Generation_Result {

	/**
	 * @param array{gateway_id:string, xpub:string, generated_addresses:array<Bitcoin_Address>, generated_addresses_count:int, generated_addresses_post_ids:array<int>, address_index:int} $new_addresses
	 */
	public function __construct(
		public Bitcoin_Wallet $wallet,
		public array $new_addresses,
		public int $address_index,
	) {
	}
}
