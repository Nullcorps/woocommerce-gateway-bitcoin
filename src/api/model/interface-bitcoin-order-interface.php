<?php
/**
 *
 *
 * @package brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\API\Model;

use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address;
use BrianHenryIE\WP_Bitcoin_Gateway\Brick\Math\BigNumber;
use BrianHenryIE\WP_Bitcoin_Gateway\Brick\Money\Money;

interface Bitcoin_Order_Interface {

	public function get_btc_total_price(): Money;

	public function get_btc_exchange_rate(): BigNumber;

	public function get_address(): Bitcoin_Address;

	// public function get_id();
	// public function get_status();
	// public function get_date_created();
	// public function add_order_note();
	// public function payment_complete();
	// public function is_paid();
	// public function save();
	// public function get_currency();
	// public function get_date_paid();
}
