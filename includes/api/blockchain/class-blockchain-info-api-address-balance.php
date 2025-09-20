<?php

namespace BrianHenryIE\WP_Bitcoin_Gateway\API\Blockchain;

use BrianHenryIE\WP_Bitcoin_Gateway\API\Model\Address_Balance;
use BrianHenryIE\WP_Bitcoin_Gateway\Brick\Money\Money;

class Blockchain_Info_Api_Address_Balance implements Address_Balance {

	/**
	 * @param array{number_of_confirmations:int, unconfirmed_balance:Money, confirmed_balance:Money} $result
	 */
	public function __construct( protected array $result ) {
	}

	public function get_confirmed_balance(): Money {
		return $this->result['confirmed_balance'];
	}

	public function get_unconfirmed_balance(): Money {
		return $this->result['unconfirmed_balance'];
	}

	public function get_number_of_confirmations(): int {
		return $this->result['number_of_confirmations'];
	}
}
