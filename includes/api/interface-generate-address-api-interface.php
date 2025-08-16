<?php
/**
 * @package    brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\API;

interface Generate_Address_API_Interface {

	public function generate_address( string $public_address, int $nth ): string;
}
