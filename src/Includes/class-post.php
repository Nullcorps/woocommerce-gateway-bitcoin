<?php
/**
 * Add a custom post type for Bitcoin address.
 * Will have statuses 'unused', 'used', 'assigned'.
 * Will have postmeta for:
 * * its derive path
 * * which order it is for
 * * its transactions
 * * its balance
 * Its parent will be its xpub.
 *
 * WP_List_Table can show all addresses and their orders and balances and last activity date.
 */

// get_defined_vars()

namespace Nullcorps\WC_Gateway_Bitcoin\Includes;

use Nullcorps\WC_Gateway_Bitcoin\API\Address_Storage\Crypto_Address;
use Nullcorps\WC_Gateway_Bitcoin\API\Address_Storage\Crypto_Wallet;

/**
 * @see wp-admin/edit.php?post_type=bh-crypto-wallet
 * @see wp-admin/edit.php?post_type=bh-crypto-address
 */
class Post {

	/**
	 * @hooked init
	 */
	public function register_wallet_post_type(): void {

		$labels = array(
			'name'          => _x( 'Crypto Wallets', 'post type general name', 'nullcorps-wc-gateway-bitcoin' ),
			'singular_name' => _x( 'Crypto Wallet', 'post type singular name', 'nullcorps-wc-gateway-bitcoin' ),
			'menu_name'     => 'Crypto Wallets',
		);

		$args = array(
			'labels'        => $labels,
			'description'   => 'Wallets used with WooCommerce Bitcoin gateways.',
			'public'        => true,
			'menu_position' => 8,
			'supports'      => array( 'title', 'thumbnail', 'excerpt', 'comments' ),
			'has_archive'   => false,
		);

		register_post_type( CRYPTO_WALLET::POST_TYPE, $args );

		register_post_status(
			'active',
			array(
				'label'                     => _x( 'Active', 'post' ),
				'public'                    => true,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>' ),
			)
		);
	}

	/**
	 * @hooked init
	 */
	public function register_address_post_type(): void {

		$labels = array(
			'name'          => _x( 'Crypto Addresses', 'post type general name', 'nullcorps-wc-gateway-bitcoin' ),
			'singular_name' => _x( 'Crypto Address', 'post type singular name', 'nullcorps-wc-gateway-bitcoin' ),
			'menu_name'     => 'Crypto Addresses',
		);
		$args   = array(
			'labels'        => $labels,
			'description'   => 'Addresses used with WooCommerce Bitcoin gateways.',
			'public'        => true,
			'menu_position' => 8,
			'supports'      => array( 'title', 'thumbnail', 'excerpt', 'comments' ),
			'has_archive'   => false,
		);
		register_post_type( CRYPTO_ADDRESS::POST_TYPE, $args );

		register_post_status(
			'unknown',
			array(
				'post_type'                 => array( Crypto_Address::POST_TYPE ),
				'label'                     => _x( 'Unknown', 'post' ),
				'public'                    => true,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Unknown <span class="count">(%s)</span>', 'Unknown <span class="count">(%s)</span>' ),
			)
		);

		register_post_status(
			'unused',
			array(
				'post_type'                 => array( Crypto_Address::POST_TYPE ),
				'label'                     => _x( 'Unused', 'post' ),
				'public'                    => true,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Unused <span class="count">(%s)</span>', 'Unused <span class="count">(%s)</span>' ),
			)
		);

		register_post_status(
			'assigned',
			array(
				'post_type'                 => array( Crypto_Address::POST_TYPE ),
				'label'                     => _x( 'Assigned', 'post' ),
				'public'                    => true,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Assigned <span class="count">(%s)</span>', 'Assigned <span class="count">(%s)</span>' ),
			)
		);

		register_post_status(
			'used',
			array(
				'post_type'                 => array( Crypto_Address::POST_TYPE ),
				'label'                     => _x( 'Used', 'post' ),
				'public'                    => true,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Used <span class="count">(%s)</span>', 'Used <span class="count">(%s)</span>' ),
			)
		);
	}

}
