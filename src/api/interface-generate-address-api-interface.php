<?php
/**
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway\API;

interface Generate_Address_API_Interface {

	public function generate_address( string $public_address, int $nth ): string;
}
