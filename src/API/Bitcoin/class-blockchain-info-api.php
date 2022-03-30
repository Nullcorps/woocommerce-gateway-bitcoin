<?php
/**
 * "Please limit your queries to a maximum of 1 every 10 seconds"
 *
 * @see https://www.blockchain.com/api/q
 *
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

namespace Nullcorps\WC_Gateway_Bitcoin\API\Bitcoin;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class Blockchain_Info_API implements Blockchain_API_Interface {
	use LoggerAwareTrait;

	public function __construct( LoggerInterface $logger ) {
		$this->logger = $logger;
	}


	public function get_received_by_address( string $btc_address, bool $confirmed ): float {

		$minimum_confirmations = $confirmed ? 1 : 0;

		$url = "https://blockchain.info/q/getreceivedbyaddress/{$btc_address}?confirmations={$minimum_confirmations}";

		$request_response = wp_remote_get( $url );

		if ( is_wp_error( $request_response ) || 200 !== $request_response['response']['code'] ) {
			throw new \Exception();
		}

		return floatval( $request_response['body'] );
	}



	public function get_address_balance( string $address, bool $confirmed ): float {

		$this->logger->debug( 'IN BLOCKCHAIN.INFO' );

		$confirmations = $confirmed ? 1 : 0;

		$this->logger->debug( 'IN CONFIRMED' );
		$url = "https://blockchain.info/q/addressbalance/{$address}?confirmations={$confirmations}";

		$request_response = wp_remote_get( $url );

		// TODO: Does "Item not found" mean address-unused?
		if ( is_wp_error( $request_response ) || 200 !== $request_response['response']['code'] ) {
			// {"message":"Item not found or argument invalid","error":"not-found-or-invalid-arg"}

			// 429
			throw new \Exception();
		}

		$balance = $request_response['body'];

		if ( $balance > 0 ) {
			$balance = $balance / 100000000;
		}
		return floatval( $balance );

	}

	/**
	 * @param string $btc_address
	 *
	 * @return array<array{txid:string, time:string, value:float}>
	 */
	public function get_transactions( string $btc_address ): array {

		$url = "https://blockchain.info/rawaddr/$btc_address";

		$request_response = wp_remote_get( $url );

		if ( is_wp_error( $request_response ) || 200 !== $request_response['response']['code'] ) {
			throw new \Exception();
		}

		$address_data = json_decode( $request_response['body'], true );

		$blockchain_transactions = $address_data['txs'] ?? array();

		$transactions = array_map(
			function( $blockchain_transaction ) {

				$txid = $blockchain_transaction['hash'];

				$value_including_fee = array_reduce(
					$blockchain_transaction['inputs'],
					function( $carry, $v_in ) {
						return $carry + $v_in['prev_out']['value'];
					},
					0
				);

				$value = ( $value_including_fee - $blockchain_transaction['fee'] ) / 100000000;

				return array(
					'txid'                          => $txid,
					'time'                          => $blockchain_transaction['time'],
					// 'confirmed' => $blockchain_transaction['status']['confirmed'],
											'value' => $value,
				);
			},
			$blockchain_transactions
		);

		return $transactions;
	}
}
