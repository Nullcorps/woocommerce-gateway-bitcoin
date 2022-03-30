<?php
/**
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

namespace Nullcorps\WC_Gateway_Bitcoin\API\Bitcoin;

interface Generate_Address_API_Interface {

	public function generate_address( string $public_address, int $nth ): string;
}
