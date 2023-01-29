<?php
/**
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway;

use Exception;
use BrianHenryIE\WC_Bitcoin_Gateway\Action_Scheduler\Background_Jobs;
use BrianHenryIE\WC_Bitcoin_Gateway\API\Addresses\Bitcoin_Address;
use BrianHenryIE\WC_Bitcoin_Gateway\API\Addresses\Bitcoin_Wallet;
use BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\Bitcoin_Gateway;
use WC_Order;


/**
 * @phpstan-type TransactionArray array{txid:string, time:\DateTimeInterface, value:string, confirmations:int}
 */
interface API_Interface {

	/**
	 * Given an order id, determine is the order's gateway an instance of this Bitcoin gateway.
	 *
	 * @see https://github.com/BrianHenryIE/bh-wc-duplicate-payment-gateways
	 *
	 * @param int $order_id
	 *
	 * @return bool
	 */
	public function is_order_has_bitcoin_gateway( int $order_id ): bool;

	/**
	 * Given a gateway id as a string, determine is it an instance of this Bitcoin gateway.
	 *
	 * @see https://github.com/BrianHenryIE/bh-wc-duplicate-payment-gateways
	 *
	 * @param string $gateway_id
	 *
	 * @return bool
	 */
	public function is_bitcoin_gateway( string $gateway_id ): bool;

	/**
	 * @return Bitcoin_Gateway[]
	 */
	public function get_bitcoin_gateways(): array;

	/**
	 *
	 * @used-by Bitcoin_Gateway::process_payment()
	 *
	 * @param string $currency USD|EUR|GBP.
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
	public function convert_fiat_to_btc( string $currency, float $fiat_amount ): string;

	/**
	 * Return an unused address for use in an order.
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
	 * @param WC_Order $order   WooCommerce order object.
	 * @param bool     $refresh Query remote APIs to refresh the details, or just return cached data.
	 *
	 * @return array{btc_address:string, bitcoin_total:string, btc_total_formatted:string, btc_price_at_at_order_time:string, last_checked_time_formatted:string, btc_amount_received_formatted:string, transactions:array<string, TransactionArray>, btc_exchange_rate:string}
	 */
	public function get_order_details( WC_Order $order, bool $refresh = true ): array;

	/**
	 * @param WC_Order $order
	 * @param bool     $refresh
	 *
	 * @return array{btc_total_formatted:string, btc_exchange_rate_formatted:string, order_status_before_formatted:string, order_status_formatted:string, btc_amount_received_formatted:string, last_checked_time_formatted:string}
	 * @throws Exception
	 */
	public function get_formatted_order_details( WC_Order $order, bool $refresh = true ): array;

	/**
	 * When a new wallet address is saved in the gateway settings, generate a Wallet custom post for it, and prepare
	 * fresh addresses for use.
	 *
	 * @used-by Bitcoin_Gateway::process_admin_options()
	 *
	 * @param string  $xpub_after
	 * @param ?string $gateway_id
	 *
	 * @return array{wallet:Bitcoin_Wallet, wallet_post_id:int, existing_fresh_addresses:array<Bitcoin_Address>, generated_addresses:array<Bitcoin_Address>}
	 */
	public function generate_new_wallet( string $xpub_after, string $gateway_id = null ): array;

	/**
	 * @return array<string, array{}|array{wallet_post_id:int, new_addresses: array{gateway_id:string, xpub:string, generated_addresses:array<Bitcoin_Address>, generated_addresses_count:int, generated_addresses_post_ids:array<int>, address_index:int}}>
	 */
	public function generate_new_addresses(): array;

	public function generate_new_addresses_for_wallet( string $xpub, int $generate_count = 25 ): array;

	/**
	 * @used-by CLI::check_transactions()
	 *
	 * @param Bitcoin_Address $address
	 *
	 * @return array{address:Bitcoin_Address, transactions:array<string, TransactionArray>, updated:bool, updates:array{new_transactions:array<string, TransactionArray>, new_confirmations:array<string, TransactionArray>}, previous_transactions:array<string, TransactionArray>|null}
	 */
	public function query_api_for_address_transactions( Bitcoin_Address $address ): array;

	/**
	 * Determine do we have any fresh address available for this gateway.
	 * Used so the gateway is not displayed at checkout if there are no addresses ready.
	 *
	 * @used-by Bitcoin_Gateway::is_available()
	 *
	 * @param Bitcoin_Gateway $gateway
	 *
	 * @return bool
	 */
	public function is_fresh_address_available_for_gateway( Bitcoin_Gateway $gateway ): bool;

	/**
	 *
	 *
	 * @used-by Background_Jobs::check_new_addresses_for_transactions()
	 * @used-by API::generate_new_addresses()
	 * @used-by API::generate_new_addresses_for_wallet()
	 * @used-by API::generate_new_wallet()
	 * @used-by CLI::generate_new_addresses()
	 *
	 * @param ?Bitcoin_Address[] $addresses
	 *
	 * @return array<string, array{address:Bitcoin_Address, transactions:array<string, TransactionArray>, updated:bool, updates:array{new_transactions:array<string, TransactionArray>, new_confirmations:array<string, TransactionArray>}, previous_transactions:array<string, TransactionArray>|null}>
	 */
	public function check_new_addresses_for_transactions( ?array $addresses = null ): array;

	/**
	 * Check does the server have the required GMP extension installed.
	 */
	public function is_server_has_dependencies(): bool;
}
