<?php
/**
 * SoChain public blockchain API.
 *
 * @see https://chain.so/api/
 *
 * The public infrastructure for SoChain allows 300 requests/minute free-of-charge.
 *
 * Is this a bad API to use?
 * @see https://twitter.com/c_otto83/status/1372988100629106688
 *
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway\API\Blockchain;

use BrianHenryIE\WC_Bitcoin_Gateway\API\Blockchain_API_Interface;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use BrianHenryIE\WC_Bitcoin_Gateway\API_Interface;
use PHPUnit\Util\Exception;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Queries for v2 SoChain API.
 *
 * @phpstan-import-type TransactionArray from API_Interface as TransactionArray
 */
class SoChain_API implements Blockchain_API_Interface {
	use LoggerAwareTrait;

	public function __construct( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * The SoChain API base.
	 */
	protected string $api_base = 'https://chain.so/api/v2/';

	public function get_received_by_address( string $btc_address, bool $confirmed ): string {
		// TODO: Implement get_received_by_address() method.

		throw new \Exception( 'Not implemented' );

		return '0.0';
	}

	/**
	 * Query the address balance.
	 *
	 * TODO: NB: If an address was reused, the previous balance would confuse the plugin into thinking the order had been paid.
	 *
	 * @param string $btc_address The address to query.
	 * @param int    $number_of_confirmations Minimum number of confirmations (completed blocks since receiving transaction block).
	 *
	 * @see Blockchain_API_Interface::get_address_balance()
	 *
	 * @return array{confirmed_balance:string, unconfirmed_balance:string, number_of_confirmations:int}
	 * @throws Exception On request failure.
	 * @throws Exception On error response.
	 */
	public function get_address_balance( string $btc_address, int $number_of_confirmations ): array {

		$endpoint = "{$this->api_base}get_address_balance/BTC/{$btc_address}/{$number_of_confirmations}";

		$request_response = wp_remote_get( $endpoint );

		if ( is_wp_error( $request_response ) ) {
			throw new Exception( $request_response->get_error_message() );
		}
		if ( 200 !== $request_response['response']['code'] ) {
			$this->logger->error( 'SoChain returned code ' . $request_response['response']['code'], $request_response );
			throw new \Exception( 'SoChain responded with error when querying address balance for ' . $btc_address );
		}

		/**
		 * The `confirmed_balance` and `unconfirmed_balance` returned.
		 *
		 * @var array{success:string, data:array{network:string,address:string,confirmed_balance:string,unconfirmed_balance:string}} $address_balance_result
		 */
		$address_balance_result = json_decode( $request_response['body'], true );

		return array(
			'confirmed_balance'       => $address_balance_result['data']['confirmed_balance'],
			'unconfirmed_balance'     => $address_balance_result['data']['unconfirmed_balance'],
			'number_of_confirmations' => $number_of_confirmations,
		);

	}

	/**
	 * Query for the set of transactions received at an address.
	 *
	 * `GET /api/v2/get_tx_received/{NETWORK}/{ADDRESS}[/{AFTER TXID}]`
	 *
	 * @see https://chain.so/api/#get-received-tx
	 * @see Blockchain_API_Interface::get_transactions_received()
	 *
	 * @param string $btc_address The address to query.
	 *
	 * @return array<string, array{txid:string, time:DateTimeInterface, value:string, confirmations:int}> Txid, data.
	 * @throws Exception On request failure.
	 * @throws Exception On error response.
	 */
	public function get_transactions_received( string $btc_address ): array {

		$endpoint = "{$this->api_base}get_tx_received/BTC/{$btc_address}";

		$request_response = wp_remote_get( $endpoint );

		// Wp_error means it failed locally before the network.
		if ( is_wp_error( $request_response ) ) {
			throw new Exception( $request_response->get_error_message() );
		}

		// Bad request or server.
		if ( 200 !== $request_response['response']['code'] ) {
			$this->logger->error( 'SoChain returned code ' . $request_response['response']['code'], $request_response );
			// 404 probably means a bad address.
			throw new \Exception( 'SoChain returned an error in get_transactions_received for ' . $btc_address );
		}

		$address_transactions_result = json_decode( $request_response['body'], true, 512, JSON_THROW_ON_ERROR );

		if ( 'success' !== $address_transactions_result['status'] ) {
			throw new \Exception();
		}

		/**
		 * The transactions returned for this address.
		 *
		 * @var array<array{txid:string, output_no:int, script_asm:string, script_hex:string, value:string, confirmations:int, time:int}> $transactions
		 */
		$transactions = $address_transactions_result['data']['txs'];

		/**
		 * Return only the required fields and return the time as a DateTimeInterface.
		 *
		 * @param array{txid:string, output_no:int, script_asm:string, script_hex:string, value:string, confirmations:int, time:int} $transaction
		 *
		 * @return array{txid:string, time:DateTimeInterface, value:string, confirmations:int}
		 */
		$sochain_map = function( array $transaction ):array {

			$mapped                  = array();
			$mapped['txid']          = $transaction['txid'];
			$mapped['time']          = DateTime::createFromFormat( 'U', $transaction['time'], new DateTimeZone( 'UTC' ) );
			$mapped['value']         = $transaction['value'];
			$mapped['confirmations'] = $transaction['confirmations'];

			return $mapped;
		};

		/**
		 * Map the API response to the interface format.
		 *
		 * @var array<array{txid:string, time:DateTimeInterface, value:string, confirmations:int}> $mapped_transactions
		 */
		$mapped_transactions = array_map( $sochain_map, $transactions );

		$keyed_transactions = array();
		foreach ( $mapped_transactions as $mapped_transaction ) {
			$keyed_transactions[ $mapped_transaction['txid'] ] = $mapped_transaction;
		}

		return $keyed_transactions;
	}
}
