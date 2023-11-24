<?php

/**
 * "Please limit your queries to a maximum of 1 every 10 seconds"
 *
 * @see https://www.blockchain.com/api/blockchain_api
 * @see https://www.blockchain.com/api/q
 *
 * @package    brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\API\Blockchain;

use BrianHenryIE\WP_Bitcoin_Gateway\Art4\Requests\Psr\HttpClient;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Blockchain_API_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Model\Address_Balance;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Model\Transaction_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\BlockchainInfo\BlockchainInfoApi;
use BrianHenryIE\WP_Bitcoin_Gateway\BlockchainInfo\Model\TransactionOut;
use DateTimeImmutable;
use DateTimeZone;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class Blockchain_Info_Api implements Blockchain_API_Interface, LoggerAwareInterface {
	use LoggerAwareTrait;

	protected BlockchainInfoApi $api;

	/**
	 * Constructor
	 *
	 * @param LoggerInterface $logger A PSR logger.
	 */
	public function __construct( LoggerInterface $logger ) {
		$this->logger = $logger;

		// Define Requests options
		$options = array();

		$client = new HttpClient( $options );

		$this->api = new BlockchainInfoApi( $client, $client );
	}

	/**
	 *
	 * @see Blockchain_API_Interface::get_received_by_address()
	 *
	 * @param string $btc_address
	 * @param bool   $confirmed
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function get_received_by_address( string $btc_address, bool $confirmed ): string {
		return $this->api->getReceivedByAddress( $btc_address, $confirmed );
	}

	public function get_address_balance( string $btc_address, int $number_of_confirmations ): Address_Balance {

		$result                            = array();
		$result['number_of_confirmations'] = $number_of_confirmations;
		$result['unconfirmed_balance']     = $this->api->getAddressBalance( $btc_address, 0 );
		$result['confirmed_balance']       = $this->api->getAddressBalance( $btc_address, $number_of_confirmations );

		return new class( $result ) implements Address_Balance {

			protected array $result;

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
	 * @param string $btc_address
	 *
	 * @return array<string, Transaction_Interface>
	 * @throws \Exception
	 */
	public function get_transactions_received( string $btc_address ): array {
		$raw_address = $this->api->getRawAddr( $btc_address );

		$blockchain_transactions = $raw_address->getTxs();

		/**
		 * @param array $blockchain_transaction
		 *
		 * @throws \Exception
		 */
		$blockchain_mapper = function ( \BrianHenryIE\WP_Bitcoin_Gateway\BlockchainInfo\Model\Transaction $blockchain_transaction ): Transaction_Interface {

			return new class($blockchain_transaction) implements Transaction_Interface {
				private \BrianHenryIE\WP_Bitcoin_Gateway\BlockchainInfo\Model\Transaction $transaction;

				public function __construct( \BrianHenryIE\WP_Bitcoin_Gateway\BlockchainInfo\Model\Transaction $transaction ) {
					$this->transaction = $transaction;
				}

				public function get_txid(): string {
					return $this->transaction->getHash();
				}

				public function get_time(): \DateTimeInterface {
					return new DateTimeImmutable( '@' . $this->transaction->getTime(), new DateTimeZone( 'UTC' ) );
				}

				public function get_value( string $to_address ): float {

					$value_including_fee = array_reduce(
						$this->transaction->getOut(),
						function ( $carry, TransactionOut $out ) use ( $to_address ) {

							if ( $out->getAddr() === $to_address ) {
								return $carry + $out->getValue();
							}
							return $carry;
						},
						0
					);

					return $value_including_fee / 100000000;
				}

				public function get_block_height(): int {
					return $this->transaction->getBlockHeight();
				}
			};
		};

		$transactions = array_map( $blockchain_mapper, $blockchain_transactions );

		// Return the array keyed by id.
		$keyed_transactions = array();
		foreach ( $transactions as $transaction ) {
			$txid                        = (string) $transaction->get_txid();
			$keyed_transactions[ $txid ] = $transaction;
		}

		return $keyed_transactions;
	}

	/**
	 * @throws \Exception
	 */
	public function get_blockchain_height(): int {

		return $this->api->getBlockCount();
	}
}
