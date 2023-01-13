<?php
/**
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway\API\Bitcoin;

interface Exchange_Rate_API_Interface {

	public function get_exchange_rate( string $currency ): string;
}
