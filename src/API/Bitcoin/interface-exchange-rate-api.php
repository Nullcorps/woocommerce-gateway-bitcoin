<?php
/**
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

namespace Nullcorps\WC_Gateway_Bitcoin\API\Bitcoin;

interface Exchange_Rate_API_Interface {

	public function get_exchange_rate( string $currency ): string;
}
