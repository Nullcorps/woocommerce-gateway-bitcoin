<?php

namespace BrianHenryIE\WP_Bitcoin_Gateway\API\Blockchain;

use BrianHenryIE\WP_Bitcoin_Gateway\API\Model\Transaction_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\Brick\Money\Money;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

class BlockStream_Info_API_Transaction implements Transaction_Interface {

	public function __construct(
		protected array $blockstream_transaction
	) {
	}

	public function get_txid(): string {
		return (string) $this->blockstream_transaction['txid'];
	}

	public function get_time(): DateTimeInterface {

		$block_time = (int) $this->blockstream_transaction['status']['block_time'];

		return new DateTimeImmutable( '@' . $block_time, new DateTimeZone( 'UTC' ) );
	}

	public function get_value( string $to_address ): Money {
		$value_including_fee = array_reduce(
			$this->blockstream_transaction['vout'],
			function ( Money $carry, array $out ) use ( $to_address ): Money {
				if ( $out['scriptpubkey_address'] === $to_address ) {
					return $carry->plus( Money::of( $out['value'], 'BTC' ) );
				}
				return $carry;
			},
			Money::of( 0, 'BTC' )
		);

		return $value_including_fee->dividedBy( 100_000_000 );
	}

	public function get_block_height(): int {

		return $this->blockstream_transaction['status']['block_height'];

		// TODO: Confirmations was returning the block height - 1. Presumably that meant mempool/0 confirmations, but I need test data to understand.
		// Correct solution is probably to check does $blockstream_transaction['status']['block_height'] exist, else ???
		// Quick fix.
	}
}
