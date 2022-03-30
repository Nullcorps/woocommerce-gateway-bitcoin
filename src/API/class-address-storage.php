<?php
/**
 *
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

namespace Nullcorps\WC_Gateway_Bitcoin\API;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Order;

/**
 * `WP_Filesystem` is only available in admin, so the following PHPCS warning cannot be fixed as advised.
 * phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
 */
class Address_Storage {
	use LoggerAwareTrait;

	protected Settings_Interface $settings;

	public function __construct( Settings_Interface $settings, LoggerInterface $logger ) {
		$this->setLogger( $logger );
		$this->settings = $settings;
	}

	/**
	 * Return the wp-content/uploads/subfolder for storing addresses.
	 *
	 * This has been created & protected by bh-wp-private-uploads.
	 *
	 * @return string
	 * @see Private_Uploads
	 */
	protected function get_files_folder(): string {

		$upload      = wp_upload_dir( null, true );
		$upload_base = $upload['basedir'];

		return $upload_base . DIRECTORY_SEPARATOR . $this->settings->get_uploads_subdirectory_name() . DIRECTORY_SEPARATOR;

	}

	/**
	 * @param string $gateway_id
	 *
	 * @return string[]
	 */
	public function get_fresh_address_list( string $gateway_id ): array {

		$folder = $this->get_files_folder();

		$freshpath = $folder . sanitize_key( $gateway_id ) . 'addresses-fresh.txt';

		$fresh = file_get_contents( $freshpath );
		if ( is_string( $fresh ) ) {
			$arfresh = explode( "\n", $fresh );
		} else {
			// TODO Maybe warning here?
			$arfresh = array();
		}

		return array_filter( $arfresh );

	}

	/**
	 * @param string   $gateway_id
	 * @param string[] $addresses
	 *
	 * @return void
	 */
	public function save_fresh_address_list( string $gateway_id, array $addresses ): void {

		$addresses_string_list = implode( "\n", $addresses );

		$folder = $this->get_files_folder();

		$freshpath = $folder . sanitize_key( $gateway_id ) . 'addresses-fresh.txt';
		file_put_contents( $freshpath, $addresses_string_list, LOCK_EX );
	}

	/**
	 * @param string $gateway_id
	 *
	 * @return string[]
	 */
	public function get_used_address_list( string $gateway_id ): array {

		$folder   = $this->get_files_folder();
		$usedpath = $folder . DIRECTORY_SEPARATOR . sanitize_key( $gateway_id ) . '-addresses-used.txt';

		$used = file_get_contents( $usedpath );
		if ( is_string( $used ) ) {
			$arused = explode( "\n", $used );
		} else {
			$arused = array();
		}

		return $arused;
	}

	public function save_used_address( string $gateway_id, string $address ): void {
		$folder   = $this->get_files_folder();
		$usedpath = $folder . DIRECTORY_SEPARATOR . sanitize_key( $gateway_id ) . '-addresses-used.txt';

		$used = $this->get_used_address_list( $gateway_id );

		$addresses[]           = $address;
		$addresses_string_list = implode( "\n", $addresses );

		file_put_contents( $usedpath, $addresses_string_list, LOCK_EX );

	}


	public function is_in_used( string $gateway_id, string $address ): bool {

		$used_addresses = $this->get_used_address_list( $gateway_id );

		return in_array( $address, $used_addresses, true );
	}


	/**
	 * TODO: Save the order_id and gateway_id with the address.
	 *
	 * @param string   $gateway_id
	 * @param string   $btc_address
	 * @param WC_Order $order
	 *
	 * @return void
	 */
	public function mark_address_used( string $gateway_id, string $btc_address, WC_Order $order ): void {

		$folder = $this->get_files_folder();
		$this->logger->debug( 'IN WOOBTC MARK ADDRESS USED' );

		$this->logger->debug( 'Files path for addresses: ' . $folder );

		$used = $this->get_used_address_list( $gateway_id );

		if ( in_array( $btc_address, $used, true ) ) {
			$this->logger->debug( 'Address exists in used stack already, ignore' );
		} else {
			$this->save_used_address( $gateway_id, $btc_address );
		}

		$fresh_address_list = $this->get_fresh_address_list( $gateway_id );

		if ( in_array( $btc_address, $fresh_address_list, true ) ) {
			$this->logger->debug( 'DOES ADDRESS STILL EXIST IN FRESH LIST?: ' . $btc_address . 'YES IT DOES - FIXING!' );

			$fresh_address_list = array_filter(
				$fresh_address_list,
				function( $element ) use ( $btc_address ) {
					return $element !== $btc_address;
				}
			);
			$this->save_fresh_address_list( $gateway_id, $fresh_address_list );
		}

	}

}
