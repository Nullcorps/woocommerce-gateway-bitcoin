<?php
/**
 * @see https://github.com/Blockstream/esplora/blob/master/API.md
 *
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

namespace Nullcorps\WC_Gateway_Bitcoin\API\Bitcoin;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * @phpstan-type Stats array{funded_txo_count:int, funded_txo_sum:int, spent_txo_count:int, spent_txo_sum:int, tx_count:int}
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

	// confirmed true/false, false for unconfirmed balance.
	public function get_address_balance( string $btc_address, bool $confirmed ): float {

		$address_info = $this->get_address_data( $btc_address );

		if ( $confirmed ) {

			$confirmed_balance = ( $address_info['chain_stats']['funded_txo_sum'] - $address_info['chain_stats']['spent_txo_sum'] ) / 100000000;
			$this->logger->debug( 'Confirmed balance: ' . number_format( $confirmed_balance, 8 ), array( 'address_info' => $address_info ) );
			return $confirmed_balance;
		} else {

			$unconfirmed_balance = ( $address_info['mempool_stats']['funded_txo_sum'] - $address_info['mempool_stats']['spent_txo_sum'] ) / 100000000;
			$this->logger->debug( 'Unconfirmed balance: ' . number_format( $unconfirmed_balance, 8 ), array( 'address_info' => $address_info ) );
			return $unconfirmed_balance;
		}
	}

	/**
	 * The total amount in BTC received at this address.
	 *
	 * @param string $btc_address The Bitcoin address.
	 *
	 * @return float
	 */
	public function get_received_by_address( string $btc_address, bool $confirmed ): float {

		$address_info = $this->get_address_data( $btc_address );

		if ( $confirmed ) {
			return $address_info['chain_stats']['funded_txo_sum'] / 100000000;
		} else {
			return ( $address_info['chain_stats']['funded_txo_sum'] + $address_info['mempool_stats']['funded_txo_sum'] ) / 100000000;
		}

	}

	/**
	 * @param string $btc_address
	 *
	 * @return array<array{txid:string, time:string, value:float}>
	 */
	public function get_transactions( string $btc_address ): array {

		$address_info_url_bs = "https://blockstream.info/api/address/{$btc_address}/txs";

		$this->logger->debug( 'URL: ' . $address_info_url_bs );

		$request_response = wp_remote_get( $address_info_url_bs );

		if ( is_wp_error( $request_response ) || 200 !== $request_response['response']['code'] ) {
			throw new \Exception();
		}

		$blockstream_transactions = json_decode( $request_response['body'], true );

		$transactions = array_map(
			function( $blockstream_transaction ) {

				$txid = $blockstream_transaction['txid'];

				$value_including_fee = array_reduce(
					$blockstream_transaction['vin'],
					function( $carry, $v_in ) {
						return $carry + $v_in['prevout']['value'];
					},
					0
				);

				$value = ( $value_including_fee - $blockstream_transaction['fee'] ) / 100000000;

				return array(
					'txid'      => $txid,
					'time'      => $blockstream_transaction['status']['block_time'],
					'confirmed' => $blockstream_transaction['status']['confirmed'],
					'value'     => $value,
				);
			},
			$blockstream_transactions
		);

		return $transactions;
	}
}
