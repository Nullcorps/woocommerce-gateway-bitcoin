<?php

namespace BrianHenryIE\WP_Bitcoin_Gateway\API\Blockchain;

use BrianHenryIE\WP_Bitcoin_Gateway\API\Model\Transaction_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\BlockchainInfo\Model\Transaction;
use BrianHenryIE\WP_Bitcoin_Gateway\BlockchainInfo\Model\TransactionOut;
use BrianHenryIE\WP_Bitcoin_Gateway\Brick\Money\Money;
use DateTimeZone;

class Blockchain_Info_Api_Transaction implements Transaction_Interface {

	public function __construct(
		protected Transaction $transaction
	) {
	}

	public function get_txid(): string {
		return $this->transaction->getHash();
	}

	public function get_time(): \DateTimeInterface {
		return new \DateTimeImmutable( '@' . $this->transaction->getTime(), new DateTimeZone( 'UTC' ) );
	}

	public function get_value( string $to_address ): Money {

		$value_including_fee = array_reduce(
			$this->transaction->getOut(),
			function ( Money $carry, TransactionOut $out ) use ( $to_address ) {

				if ( $out->getAddr() === $to_address ) {
					return $carry->plus( $out->getValue() );
				}
				return $carry;
			},
			Money::of( 0, 'BTC' )
		);

		return $value_including_fee->dividedBy( 100_000_000 );
	}

	public function get_block_height(): int {
		return $this->transaction->getBlockHeight();
	}
}
