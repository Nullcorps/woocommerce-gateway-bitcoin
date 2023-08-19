<?php

namespace BrianHenryIE\WP_Bitcoin_Gateway\API;

use BrianHenryIE\WP_Bitcoin_Gateway\API\Model\Transaction_Interface;

class Transaction_Formatter {


	public function get_url( Transaction_Interface $transaction ): string {
		return sprintf(
			'https://blockchain.com/explorer/transactions/btc/%s',
			$transaction->get_txid()
		);
	}

	public function get_ellipses( Transaction_Interface $transaction ): string {
		return substr( $transaction->get_txid(), 0, 3 ) . '...' . substr( $transaction->get_txid(), - 3 );
	}

	/**
	 * @param Transaction_Interface[] $new_order_transactions
	 *
	 * @return string
	 */
	public function get_order_note( array $new_order_transactions ): string {

		$note = '';
		// TODO: plural.
		$note                  .= 'New transactions seen: ';
		$new_transactions_notes = array();
		foreach ( $new_order_transactions as $new_transaction ) {
			$new_transactions_notes[] = $this->get_note_part( $new_transaction );
		}
		$note .= implode( ',', $new_transactions_notes ) . ".\n\n";

		return $note;
	}

	protected function get_note_part( Transaction_Interface $transaction ): string {
		return sprintf(
			'<a href="%s" target="_blank">%s</a>, @%s',
			esc_url( $this->get_url( $transaction ) ),
			$this->get_ellipses( $transaction ),
			$transaction->get_block_height() ?? 'mempool'
		);
	}

}
