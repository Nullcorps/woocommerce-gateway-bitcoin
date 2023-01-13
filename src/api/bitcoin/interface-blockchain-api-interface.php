<?php
/**
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway\API\Bitcoin;

use DateTimeInterface;
use BrianHenryIE\WC_Bitcoin_Gateway\API_Interface;

/**
 * @phpstan-import-type TransactionArray from API_Interface as TransactionArray
 */
interface Blockchain_API_Interface {

	/**
	 * The total amount in BTC received at this address.
	 *
	 * @param string $btc_address The Bitcoin address.
	 * @param bool   $confirmed
	 *
	 * @return string
	 */
	public function get_received_by_address( string $btc_address, bool $confirmed ): string;

	/**
	 * The current balance of the address.
	 *
	 * @param string $btc_address
	 * @param int    $number_of_confirmations
	 *
	 * @return array{confirmed_balance:string, unconfirmed_balance:string, number_of_confirmations:int}
	 */
	public function get_address_balance( string $btc_address, int $number_of_confirmations ): array;

	/**
	 * Query the Blockchain API for the transactions received at this address.
	 *
	 * @param string $btc_address
	 *
	 * @return array<string, TransactionArray> Txid, data.
	 */
	public function get_transactions_received( string $btc_address ): array;

}
