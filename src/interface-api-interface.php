<?php
/**
 * The core plugin settings that may preferably be set by supplying another instance conforming to this interface.
 *
 * @package    brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway;

use BrianHenryIE\WP_Bitcoin_Gateway\API\Model\Bitcoin_Order;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Model\Bitcoin_Order_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Model\Transaction_Interface;
use Exception;
use BrianHenryIE\WP_Bitcoin_Gateway\Action_Scheduler\Background_Jobs;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Wallet;
use BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce\Bitcoin_Gateway;
use WC_Order;

/**
 * Methods in API class that are used by other classes, primarily Bitcoin_Gateway, Background_Jobs and CLI.
 */
interface API_Interface {

	/**
	 * Given an order id, determine is the order's gateway an instance of this Bitcoin gateway.
	 *
	 * @see https://github.com/BrianHenryIE/bh-wc-duplicate-payment-gateways
	 *
	 * @param int $order_id A WooCommerce order id (presumably).
	 *
	 * @return bool
	 */
	public function is_order_has_bitcoin_gateway( int $order_id ): bool;

	/**
	 * Given a gateway id as a string, determine is it an instance of this Bitcoin gateway.
	 *
	 * @see https://github.com/BrianHenryIE/bh-wc-duplicate-payment-gateways
	 *
	 * @param string $gateway_id The WC_Payment_Gateway id to be checked.
	 *
	 * @return bool
	 */
	public function is_bitcoin_gateway( string $gateway_id ): bool;

	/**
	 * Get a list of payment gateways registered with WooCommerce which are instances of Bitcoin_Gateway.
	 *
	 * @return Bitcoin_Gateway[]
	 */
	public function get_bitcoin_gateways(): array;

	/**
	 * Find what the value of 1 BTC is in the specified currency.
	 *
	 * @used-by Bitcoin_Gateway::process_payment()
	 *
	 * @param string $currency E.g. USD|EUR|GBP.
	 *
	 * @return string
	 */
	public function get_exchange_rate( string $currency ): string;

	/**
	 * Get the Bitcoin value of a local currency amount.
	 *
	 * @used-by Bitcoin_Gateway::process_payment()
	 *
	 * @param string $currency From which currency.
	 * @param float  $fiat_amount The amount to convert.
	 *
	 * @return string
	 */
	public function convert_fiat_to_btc( string $currency, float $fiat_amount = 1.0 ): string;

	/**
	 * Return an unused address for use in an order.
	 *
	 * Adds the address as metadata to the order.
	 *
	 * @param WC_Order $order The (newly placed) WooCommerce order.
	 *
	 * @return Bitcoin_Address
	 * @throws Exception When no address is available.
	 */
	public function get_fresh_address_for_order( WC_Order $order ): Bitcoin_Address;

	/**
	 * Return the current Bitcoin details for an order, optionally refresh.
	 *
	 * @param WC_Order $wc_order   WooCommerce order object.
	 * @param bool     $refresh Query remote APIs to refresh the details, or just return cached data.
	 *
	 * @return array{btc_address:string, bitcoin_total:string, btc_total_formatted:string, btc_price_at_at_order_time:string, last_checked_time_formatted:string, btc_amount_received_formatted:string, transactions:array<string, Transaction_Interface>, btc_exchange_rate:string}
	 */
	public function get_order_details( WC_Order $wc_order, bool $refresh = true ): Bitcoin_Order_Interface;

	/**
	 * Returns the array from `get_order_details()` with additional keys for printing in HTML/email.
	 *
	 * @param WC_Order $order The WooCommerce order.
	 * @param bool     $refresh Should an API request be made to check for new transactions, or just use existing data.
	 *
	 * @return array{btc_total_formatted:string, btc_exchange_rate_formatted:string, order_status_before_formatted:string, order_status_formatted:string, btc_amount_received_formatted:string, last_checked_time_formatted:string}
	 * @throws Exception When the order has no Bitcoin address.
	 */
	public function get_formatted_order_details( WC_Order $order, bool $refresh = true ): array;

	/**
	 * When a new wallet address is saved in the gateway settings, generate a Wallet custom post for it, and prepare
	 * fresh addresses for use.
	 *
	 * @used-by Bitcoin_Gateway::process_admin_options()
	 *
	 * @param string  $master_public_key The wallet address to save as a wallet object cpt.
	 * @param ?string $gateway_id The Bitcoin gateway (it is presumably linked to one).
	 *
	 * @return array{wallet:Bitcoin_Wallet, wallet_post_id:int, existing_fresh_addresses:array<Bitcoin_Address>, generated_addresses:array<Bitcoin_Address>}
	 */
	public function generate_new_wallet( string $master_public_key, string $gateway_id = null ): array;

	/**
	 * For each Bitcoin gateway, calls `generate_new_addresses_for_wallet()`.
	 *
	 * @return array<string, array{}|array{wallet_post_id:int, new_addresses: array{gateway_id:string, xpub:string, generated_addresses:array<Bitcoin_Address>, generated_addresses_count:int, generated_addresses_post_ids:array<int>, address_index:int}}>
	 */
	public function generate_new_addresses(): array;

	/**
	 * Generate fresh addresses for a wallet.
	 *
	 * Gets the wallet object (CPT), get the last address index generated, derives the following 25 addresses for
	 * that wallet, checks the new addresses for transactions, queues a new background job to generate more if
	 * total is still below threshold.
	 *
	 * @param string $master_public_key The main wallet address (xpub/ypub/zpub).
	 * @param int    $generate_count The number of sub-addresses to derive.
	 *
	 * @return array{xpub:string, generated_addresses:array<Bitcoin_Address>, generated_addresses_count:int, generated_addresses_post_ids:array<int>, address_index:int}
	 */
	public function generate_new_addresses_for_wallet( string $master_public_key, int $generate_count = 25 ): array;

	/**
	 * Get transactions for an address object, with number of confirmations for each, and show which are new or updated.
	 *
	 * @used-by CLI::check_transactions()
	 *
	 * @param Bitcoin_Address $address Address object for existing saved address (i.e. this doesn't work for arbitrary addresses).
	 *
	 * @return array{address:Bitcoin_Address, transactions:array<string, Transaction_Interface>, updated:bool, updates:array{new_transactions:array<string, TransactionArray>, new_confirmations:array<string, TransactionArray>}, previous_transactions:array<string, TransactionArray>|null}
	 */
	public function update_address_transactions( Bitcoin_Address $address ): array;

	/**
	 * Determine do we have any fresh address available for this gateway.
	 * Used so the gateway is not displayed at checkout if there are no addresses ready.
	 *
	 * @used-by Bitcoin_Gateway::is_available()
	 *
	 * @param Bitcoin_Gateway $gateway The WooCommerce payment gateway which should have addresses generated.
	 *
	 * @return bool
	 */
	public function is_fresh_address_available_for_gateway( Bitcoin_Gateway $gateway ): bool;

	/**
	 * Validate addresses have not been used before by checking for transactions.
	 *
	 * @used-by Background_Jobs::check_new_addresses_for_transactions()
	 * @used-by API::generate_new_addresses()
	 * @used-by API::generate_new_addresses_for_wallet()
	 * @used-by API::generate_new_wallet()
	 * @used-by CLI::generate_new_addresses()
	 *
	 * @param ?Bitcoin_Address[] $addresses Array of Bitcoin address objects, or omit the parameter to check generated addresses whose status is "unknown".
	 *
	 * @return array<string, array{address:Bitcoin_Address, transactions:array<string, Transaction_Interface>}>
	 */
	public function check_new_addresses_for_transactions(): array;

	/**
	 * Check does the server have the required GMP extension installed.
	 */
	public function is_server_has_dependencies(): bool;
}
