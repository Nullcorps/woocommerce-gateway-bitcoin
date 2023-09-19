<?php
/**
 * @see https://github.com/Blockstream/esplora/blob/master/API.md
 *
 * @package    brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\API\Blockchain;

use BrianHenryIE\WP_Bitcoin_Gateway\API\Blockchain_API_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Model\Address_Balance;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Model\Transaction_Interface;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use BrianHenryIE\WP_Bitcoin_Gateway\API_Interface;
use JsonException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * @phpstan-type Stats array{funded_txo_count:int, funded_txo_sum:int, spent_txo_count:int, spent_txo_sum:int, tx_count:int}
 * @phpstan-import-type TransactionArray from API_Interface as TransactionArray
 */
class Blockstream_Info_API implements Blockchain_API_Interface, LoggerAwareInterface {
	use LoggerAwareTrait;

	public function __construct( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 *
	 * @return array{address:string, chain_stats:Stats, mempool_stats:Stats}
	 */
	protected function get_address_data( string $btc_address ): array {
		$address_info_url = 'https://blockstream.info/api/address/' . $btc_address;

		$this->logger->debug( 'URL: ' . $address_info_url );

		$request_response = wp_remote_get( $address_info_url );

		if ( is_wp_error( $request_response ) || 200 !== $request_response['response']['code'] ) {
			throw new \Exception();
		}

		$address_info = json_decode( $request_response['body'], true );

		return $address_info;
	}

	/**
	 * @see Blockchain_API_Interface::get_address_balance()
	 *
	 * @param string $btc_address
	 * @param int    $number_of_confirmations
	 *
	 * @return array{confirmed_balance:string, unconfirmed_balance:string, number_of_confirmations:int}
	 * @throws \Exception
	 */
	public function get_address_balance( string $btc_address, int $number_of_confirmations ): Address_Balance {

		if ( 1 !== $number_of_confirmations ) {
			error_log( __CLASS__ . ' ' . __FUNCTION__ . ' using 1 for number of confirmations.' );

			// Maybe `number_of_confirmations` should be block_height and the client can decide is that enough.
		}

		$result                            = array();
		$result['number_of_confirmations'] = $number_of_confirmations;

		$address_info = $this->get_address_data( $btc_address );

		$confirmed_balance = ( $address_info['chain_stats']['funded_txo_sum'] - $address_info['chain_stats']['spent_txo_sum'] ) / 100000000;
		$this->logger->debug( 'Confirmed balance: ' . number_format( $confirmed_balance, 8 ), array( 'address_info' => $address_info ) );

		$result['confirmed_balance'] = (string) $confirmed_balance;

		$unconfirmed_balance = ( $address_info['mempool_stats']['funded_txo_sum'] - $address_info['mempool_stats']['spent_txo_sum'] ) / 100000000;
		$this->logger->debug( 'Unconfirmed balance: ' . number_format( $unconfirmed_balance, 8 ), array( 'address_info' => $address_info ) );

		$result['unconfirmed_balance'] = (string) $unconfirmed_balance;

		return new class($result) implements Address_Balance {
			protected $result;
			public function __construct( $result ) {
				$this->result = $result;
			}

			public function get_confirmed_balance(): string {
				return $this->result['confirmed_balance'];
			}

			public function get_unconfirmed_balance(): string {
				return $this->result['unconfirmed_balance'];
			}

			public function get_number_of_confirmations(): int {
				return $this->result['number_of_confirmations'];
			}
		};
	}

	/**
	 * The total amount in BTC received at this address.
	 *
	 * @param string $btc_address The Bitcoin address.
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function get_received_by_address( string $btc_address, bool $confirmed ): string {

		$address_info = $this->get_address_data( $btc_address );

		if ( $confirmed ) {
			$calc = $address_info['chain_stats']['funded_txo_sum'] / 100000000;
		} else {
			$calc = ( $address_info['chain_stats']['funded_txo_sum'] + $address_info['mempool_stats']['funded_txo_sum'] ) / 100000000;
		}
		return "{$calc}";
	}

	/**
	 * @param string $btc_address
	 *
	 * @return array<string, Transaction_Interface>
	 *
	 * @throws JsonException
	 */
	public function get_transactions_received( string $btc_address ): array {

		$address_info_url_bs = "https://blockstream.info/api/address/{$btc_address}/txs";

		$this->logger->debug( 'URL: ' . $address_info_url_bs );

		$request_response = wp_remote_get( $address_info_url_bs );

		if ( is_wp_error( $request_response ) ) {
			throw new \Exception( $request_response->get_error_message() );
		}
		if ( 200 !== $request_response['response']['code'] ) {
			throw new \Exception( 'Unexpected response received.' );
		}

		$blockstream_transactions = json_decode( $request_response['body'], true, 512, JSON_THROW_ON_ERROR );

		/**
		 * block_time is in unixtime.
		 *
		 * @param array{txid:string, version:int, locktime:int, vin:array, vout:array, size:int, weight:int, fee:int, status:array{confirmed:bool, block_height:int, block_hash:string, block_time:int}} $blockstream_transaction
		 *
		 * @return Transaction_Interface
		 */
		$blockstream_mapper = function ( array $blockstream_transaction ): Transaction_Interface {

			return new class( $blockstream_transaction ) implements Transaction_Interface {

				protected $blockstream_transaction;

				public function __construct( $blockstream_transaction ) {
					$this->blockstream_transaction = $blockstream_transaction;
				}

				public function get_txid(): string {
					return (string) $this->blockstream_transaction['txid'];
				}

				public function get_time(): \DateTimeInterface {

					$block_time = (int) $this->blockstream_transaction['status']['block_time'];

					return new DateTimeImmutable( '@' . $block_time, new DateTimeZone( 'UTC' ) );
				}

				public function get_value( string $to_address ): float {
					$value_including_fee = array_reduce(
						$this->blockstream_transaction['vout'],
						function ( $carry, $out ) use ( $to_address ) {
							if ( $out['scriptpubkey_address'] === $to_address ) {
								return $carry + $out['value'];
							}
							return $carry;
						},
						0
					);

					return $value_including_fee / 100000000;
				}

				public function get_block_height(): int {

					return $this->blockstream_transaction['status']['block_height'];

					// TODO: Confirmations was returning the block height - 1. Presumably that meant mempool/0 confirmations, but I need test data to understand.
					// Correct solution is probably to check does $blockstream_transaction['status']['block_height'] exist, else 0.
					// Quick fix.
				}
			};
		};

		$transactions = array_map( $blockstream_mapper, $blockstream_transactions );

		$keyed_transactions = array();
		foreach ( $transactions as $transaction ) {
			$keyed_transactions[ $transaction->get_txid() ] = $transaction;
		}

		return $keyed_transactions;
	}

	/**
	 * @return int
	 * @throws \Exception
	 */
	public function get_blockchain_height(): int {
		$blocks_url_bs    = 'https://blockstream.info/api/blocks/tip/height';
		$request_response = wp_remote_get( $blocks_url_bs );
		if ( is_wp_error( $request_response ) || 200 !== $request_response['response']['code'] ) {
			throw new \Exception();
		}
		return intval( $request_response['body'] );
	}
}
