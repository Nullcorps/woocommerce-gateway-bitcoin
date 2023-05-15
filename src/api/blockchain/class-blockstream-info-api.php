<?php
/**
 * @see https://github.com/Blockstream/esplora/blob/master/API.md
 *
 * @package    brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\API\Blockchain;

use BrianHenryIE\WP_Bitcoin_Gateway\API\Blockchain_API_Interface;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use BrianHenryIE\WP_Bitcoin_Gateway\API_Interface;
use JsonException;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * @phpstan-type Stats array{funded_txo_count:int, funded_txo_sum:int, spent_txo_count:int, spent_txo_sum:int, tx_count:int}
 * @phpstan-import-type TransactionArray from API_Interface as TransactionArray
 */
class Blockstream_Info_API implements Blockchain_API_Interface {
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
	public function get_address_balance( string $btc_address, int $number_of_confirmations ): array {

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

		return $result;
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
	 * @return array<string, array{txid:string, time:DateTimeInterface, value:string, confirmations:int}>
	 *
	 * @throws JsonException
	 */
	public function get_transactions_received( string $btc_address ): array {
		$blockchain_height = $this->get_blockchain_height();

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
		 * @return array{txid:string, time:DateTimeInterface, value:numeric-string, confirmations:int}
		 */
		$blockstream_mapper = function( array $blockstream_transaction ) use ( $blockchain_height ) : array {

			$txid = (string) $blockstream_transaction['txid'];

			$value_including_fee = array_reduce(
				$blockstream_transaction['vin'],
				function( $carry, $v_in ) {
					return $carry + $v_in['prevout']['value'];
				},
				0
			);

			$value = (string) ( ( $value_including_fee - $blockstream_transaction['fee'] ) / 100000000 );

			$confirmations = (int) ( $blockchain_height - $blockstream_transaction['status']['block_height'] );

			// TODO: Confirmations was returning the block height - 1. Presumably that meant mempool/0 confirmations, but I need test data to understand.
			// Quick fix.
			if ( $confirmations === $blockchain_height || $confirmations === $blockchain_height - 1 ) {
				$confirmations = 0;
			}

			$block_time = (int) $blockstream_transaction['status']['block_time'];

			return array(
				'txid'          => $txid,
				'time'          => new DateTimeImmutable( '@' . $block_time, new DateTimeZone( 'UTC' ) ),
				'value'         => $value,
				'confirmations' => $confirmations,
			);
		};

		$transactions_received = array_filter(
			$blockstream_transactions,
			function( array $transaction ) use ( $btc_address ): bool {
				// Determine did this transaction pay TO our Bitcoin address?
				return array_reduce(
					$transaction['vout'],
					function( bool $carry, array $vout ) use ( $btc_address ): bool {
						return $carry || $btc_address === $vout['scriptpubkey_address'];
					},
					false
				);
			}
		);

		$transactions = array_map( $blockstream_mapper, $transactions_received );

		$keyed_transactions = array();
		foreach ( $transactions as $transaction ) {
			$keyed_transactions[ $transaction['txid'] ] = $transaction;
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
