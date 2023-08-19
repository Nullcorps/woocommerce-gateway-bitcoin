<?php
/**
 * WP CLI commands for invoking API functions.
 *
 * Most useful to check an order for payment without waiting for Action Scheduler.
 *
 * @package    brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\WP_Includes;

use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address_Factory;
use BrianHenryIE\WP_Bitcoin_Gateway\API_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\Settings_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce\Order;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Order;
use WP_CLI;
use WP_CLI_Command;

/**
 * Run `wp bh-bitcoin help` for documentation.
 */
class CLI extends WP_CLI_Command {
	use LoggerAwareTrait;

	/**
	 * Not used.
	 *
	 * @var Settings_Interface
	 */
	protected Settings_Interface $settings;

	/**
	 * All CLI functions call into an instance of the API_Interface.
	 *
	 * @var API_Interface $api The main plugin API definition.
	 */
	protected API_Interface $api;

	/**
	 * Constructor.
	 *
	 * @param API_Interface      $api The main plugin functions.
	 * @param Settings_Interface $settings The plugin's settings.
	 * @param LoggerInterface    $logger A PSR logger.
	 */
	public function __construct( API_Interface $api, Settings_Interface $settings, LoggerInterface $logger ) {
		parent::__construct();
		$this->setLogger( $logger );
		$this->settings = $settings;
		$this->api      = $api;
	}

	/**
	 * Generate new addresses for all gateways.
	 *
	 * ## OPTIONS
	 *
	 * [--<debug>=bh-wp-bitcoin-gateway]
	 * : Show detailed progress.
	 *
	 * ## EXAMPLES
	 *
	 *   # Check for new transactions for all gateways.
	 *   $ wp bh-bitcoin generate-new-addresses
	 *
	 *   # Check for new transactions for all gateways and show detailed progress.
	 *   $ wp bh-bitcoin generate-new-addresses --debug=bh-wp-bitcoin-gateway
	 *
	 * @param array<int|string, string> $args Takes no arguments.
	 */
	public function generate_new_addresses( array $args ): void {

		$result = $this->api->generate_new_addresses();
		$this->api->check_new_addresses_for_transactions();

		// TODO: Print a table of new addresses and their status.
		// Print a summary of the table.

		WP_CLI::log( 'Finished generate-new-addresses.' );
	}

	/**
	 * Query the blockchain for updates for an address or order.
	 *
	 * TODO: This doesn't seem to actually update the order!
	 *
	 * See also: `wp post list --post_type=shop_order --post_status=wc-on-hold --meta_key=_payment_gateway --meta_value=bitcoin_gateway --format=ids`.
	 * `wp post list --post_type=shop_order --post_status=wc-on-hold --meta_key=_payment_gateway --meta_value=bitcoin_gateway --format=ids | xargs -0 -d ' ' -I % wp bh-bitcoin check-transactions % --debug=bh-wp-bitcoin-gateway`
	 *
	 *
	 * ## OPTIONS
	 *
	 * <input>
	 * : The order id or Bitcoin address.
	 *
	 * [--format=<format>]
	 * Render output in a specific format.
	 * ---
	 * default: table
	 * options:
	 * - table
	 * - json
	 * - csv
	 * - yaml
	 * ---
	 *
	 * [--<debug>=bh-wp-bitcoin-gateway]
	 * : Show detailed progress.
	 *
	 * ## EXAMPLES
	 *
	 *   # Check for new transactions for the provided Bitcoin address
	 *   $ wp bh-bitcoin check-transactions 0a1b2c3e4f6g7h9
	 *
	 *   # Check for new transactions for the provided order
	 *   $ wp bh-bitcoin check-transactions 123
	 *
	 *   # Check for new transactions for the provided order, showing detailed progress.
	 *   $ wp bh-bitcoin check-transactions 123 --debug=bh-wp-bitcoin-gateway
	 *
	 * @param string[]             $args The address.
	 * @param array<string,string> $assoc_args List of named arguments.
	 *
	 * @throws WP_CLI\ExitException When given input that does not match a known xpub, or post_id for a bitcoin address or relevant WooCommerce order.
	 */
	public function check_transactions( array $args, array $assoc_args ): void {

		$input  = $args[0];
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';

		$address_factory = new Bitcoin_Address_Factory();

		try {
			switch ( get_post_type( intval( $input ) ) ) {
				case Bitcoin_Address::POST_TYPE:
					$this->logger->debug( "CLI input was `bh-bitcoin-address:{$input}`" );
					$bitcoin_address = new Bitcoin_Address( intval( $input ) );
					break;
				case 'shop_order':
					$order_id = intval( $input );
					$this->logger->debug( "CLI input was WooCommerce `shop_order:{$order_id}`" );
					/**
					 * This was already determined to be an order!
					 *
					 * @var WC_Order $order
					 */
					$order = wc_get_order( $order_id );
					if ( ! $this->api->is_order_has_bitcoin_gateway( $order_id ) ) {
						$this->logger->error( "`shop_order:{$order_id}` is not a Bitcoin order" );
						return;
					}
					$address                 = $order->get_meta( Order::BITCOIN_ADDRESS_META_KEY );
					$bitcoin_address_post_id = $address_factory->get_post_id_for_address( $address );
					if ( is_null( $bitcoin_address_post_id ) ) {
						$this->logger->error( "Could not find Bitcoin address object for address {$address} from order id {$input}." );
						return;
					}
					$bitcoin_address = $address_factory->get_by_post_id( $bitcoin_address_post_id );
					break;
				default:
					// Assuming a raw address has been input.
					$bitcoin_address_post_id = $address_factory->get_post_id_for_address( $input );
					if ( is_null( $bitcoin_address_post_id ) ) {
						$this->logger->error( "Could not find Bitcoin address object for {$input}." );
						return;
					}
					$bitcoin_address = $address_factory->get_by_post_id( $bitcoin_address_post_id );
			}

			$result = $this->api->update_address_transactions( $bitcoin_address );

			// TODO: Check for WooCommerce active.

			$formatted = array(
				'address' => $result['address']->get_raw_address(),
				'updated' => wc_bool_to_string( $result['updated'] ),
			);

			if ( $result['updated'] ) {
				$formatted['new_transactions']  = $result['updates']['new_transactions'];
				$formatted['new_confirmations'] = $result['updates']['new_confirmations'];
			}

			$formatted['balance'] = $result['address']->get_balance();

			WP_CLI\Utils\format_items( $format, $formatted, array_keys( $formatted ) );

			WP_CLI::log( 'Finished update-address.' );

		} catch ( \Exception $exception ) {
			WP_CLI::error( $exception->getMessage() );
		}
	}

}
