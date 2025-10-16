<?php
/**
 * Model class representing the result of generating a new wallet.
 *
 * @package brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\API\Model;

use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Wallet;
use BrianHenryIE\WP_Bitcoin_Gateway\API_Interface;

/**
 * Wallet generation result model.
 *
 * @see API::generate_new_wallet()
 * @see API_Interface::generate_new_wallet()
 */
class Wallet_Generation_Result {

	/**
	 * Constructor.
	 *
	 * @param Bitcoin_Wallet    $wallet The relevant wallet addresses were generated for.
	 * @param Bitcoin_Address[] $existing_fresh_addresses List of as-yet unused addresses.
	 * @param Bitcoin_Address[] $generated_addresses List of newly generated addresses.
	 */
	public function __construct(
		protected Bitcoin_Wallet $wallet,
		protected array $existing_fresh_addresses,
		protected array $generated_addresses
	) {
	}

	/**
	 * Get the wallet object.
	 */
	public function get_wallet(): Bitcoin_Wallet {
		return $this->wallet;
	}

	/**
	 * Get existing fresh addresses.
	 *
	 * @return Bitcoin_Address[]
	 */
	public function get_existing_fresh_addresses(): array {
		return $this->existing_fresh_addresses;
	}

	/**
	 * Get newly generated addresses.
	 *
	 * @return Bitcoin_Address[]
	 */
	public function get_generated_addresses(): array {
		return $this->generated_addresses;
	}

	/**
	 * Convert to array format for backward compatibility.
	 *
	 * @return array{wallet: Bitcoin_Wallet, existing_fresh_addresses: array<Bitcoin_Address>, generated_addresses: array<Bitcoin_Address>}
	 */
	public function to_array(): array {
		return array(
			'wallet'                   => $this->wallet,
			'existing_fresh_addresses' => $this->existing_fresh_addresses,
			'generated_addresses'      => $this->generated_addresses,
		);
	}
}
