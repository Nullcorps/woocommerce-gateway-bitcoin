<?php
/**
 * Register the admin table UIs for wallets and addresses.
 *
 * @package brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\Admin;

use WP_Screen;

/**
 * Return our WP_List_Table subclasses.
 *
 * @see _get_list_table()
 */
class Register_List_Tables {

	/**
	 * Use the `Wallets_List_Table` class on the `wp-admin/edit.php?post_type=bh-bitcoin-wallet` screen.
	 *
	 * @see _get_list_table()
	 * @hooked wp_list_table_class_name
	 *
	 * @param string                         $class_name Default WP_Posts_List_Table class to instantiate.
	 * @param array{screen?:WP_Screen|mixed} $args An array containing _get_list_table() arguments, which is seemingly just 'screen'.
	 *
	 * @return string
	 */
	public function register_bitcoin_wallet_table( string $class_name, array $args ): string {

		if ( isset( $args['screen'] ) && ( $args['screen'] instanceof WP_Screen ) && 'edit-bh-bitcoin-wallet' === $args['screen']->id ) {
			return Wallets_List_Table::class;
		}

		return $class_name;
	}

	/**
	 * Use the `Addresses_List_Table` class on the `wp-admin/edit.php?post_type=bh-bitcoin-address` screen.
	 *
	 * @see _get_list_table()
	 * @hooked wp_list_table_class_name
	 *
	 * @param string                         $class_name Default WP_Posts_List_Table class to instantiate.
	 * @param array{screen?:WP_Screen|mixed} $args An array containing _get_list_table() arguments, which is seemingly just 'screen'.
	 *
	 * @return string
	 */
	public function register_bitcoin_address_table( string $class_name, array $args ): string {

		if ( isset( $args['screen'] ) && ( $args['screen'] instanceof WP_Screen ) && 'edit-bh-bitcoin-address' === $args['screen']->id ) {
			return Addresses_List_Table::class;
		}

		return $class_name;
	}
}
