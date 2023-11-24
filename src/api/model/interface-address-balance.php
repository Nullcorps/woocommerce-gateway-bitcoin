<?php
/**
 *
 *
 * @package brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\API\Model;

interface Address_Balance {

	/**
	 * @return string
	 */
	public function get_confirmed_balance(): string;

	/**
	 * @return string
	 */
	public function get_unconfirmed_balance(): string;

	/**
	 * @return int
	 */
	public function get_number_of_confirmations(): int;
}
