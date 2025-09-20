<?php
/**
 *
 *
 * @package brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\API\Model;

use BrianHenryIE\WP_Bitcoin_Gateway\Brick\Money\Money;
use DateTimeInterface;

interface Transaction_Interface {

	/**
	 * @return string
	 */
	public function get_txid(): string;

	/**
	 * Used to filter transactions to only those between the time the order was placed, and paid.
	 */
	public function get_time(): DateTimeInterface;

	/**
	 * @param string $to_address
	 */
	public function get_value( string $to_address ): Money;

	/**
	 * Returns null for mempool.
	 */
	public function get_block_height(): ?int;
}
