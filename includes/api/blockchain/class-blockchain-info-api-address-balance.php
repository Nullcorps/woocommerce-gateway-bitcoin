<?php

namespace BrianHenryIE\WP_Bitcoin_Gateway\API\Blockchain;

use BrianHenryIE\WP_Bitcoin_Gateway\API\Model\Address_Balance;
use BrianHenryIE\WP_Bitcoin_Gateway\Brick\Money\Money;

class Blockchain_Info_Api_Address_Balance implements Address_Balance {

	public function __construct(
		protected int $number_of_confirmations,
		protected Money $unconfirmed_balance,
		protected Money $confirmed_balance,
	) {
	}

	public function get_confirmed_balance(): Money {
		return $this->confirmed_balance;
	}

	public function get_unconfirmed_balance(): Money {
		return $this->unconfirmed_balance;
	}

	public function get_number_of_confirmations(): int {
		return $this->number_of_confirmations;
	}
}
