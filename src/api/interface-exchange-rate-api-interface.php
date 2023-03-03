<?php
/**
 * @package    brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\API;

interface Exchange_Rate_API_Interface {

	public function get_exchange_rate( string $currency ): string;
}
