<?php
/**
 *
 *
 * @package brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\API\Model;

interface Transaction_Interface {

	/**
	 * @return string
	 */
	public function get_txid(): string;

	/**
	 * Used to filter transactions to only those between the time the order was placed, and paid.
	 *
	 * @return \DateTimeInterface
	 */
	public function get_time(): \DateTimeInterface;

	/**
	 * @return string
	 */
	public function get_value( string $to_address ): float;

	/**
	 * Null for mempool
	 */
	public function get_block_height(): ?int;
}
