<?php


namespace BrianHenryIE\WP_Bitcoin_Gateway\API\Model;

use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address;

interface Bitcoin_Order_Interface {

	public function get_btc_total_price(): int;

	public function get_btc_exchange_rate(): float;

	public function get_address(): Bitcoin_Address;
}
