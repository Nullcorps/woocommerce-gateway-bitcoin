<?php
/**
 * One Bitcoin equivalent in a given currency.
 *
 * @package    brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\API;

use BrianHenryIE\WP_Bitcoin_Gateway\Brick\Money\Currency;
use BrianHenryIE\WP_Bitcoin_Gateway\Brick\Money\Money;

interface Exchange_Rate_API_Interface {

	public function get_exchange_rate( Currency $currency ): Money;
}
