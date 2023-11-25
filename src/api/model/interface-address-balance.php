<?php
/**
 *
 *
 * @package brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\API\Model;

use BrianHenryIE\WP_Bitcoin_Gateway\Brick\Money\Money;

interface Address_Balance {

	public function get_confirmed_balance(): Money;

	public function get_unconfirmed_balance(): Money;

	public function get_number_of_confirmations(): int;
}
