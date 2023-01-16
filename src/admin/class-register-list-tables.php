<?php

namespace BrianHenryIE\WC_Bitcoin_Gateway\Admin;

class Register_List_Tables {

	/**
	 *
	 * @see _get_list_table()
	 *
	 * @param string                   $class_name Default WP_Posts_List_Table
	 * @param array{screen:\WP_Screen} $args
	 *
	 * @return string
	 */
	public function register_bitcoin_address_table( string $class_name, array $args ): string {

		if ( isset( $args['screen'] ) && ( $args['screen'] instanceof \WP_Screen ) && 'edit-bh-bitcoin-address' === $args['screen']->id ) {
			return Addresses_List_Table::class;
		}

		return $class_name;
	}

}
