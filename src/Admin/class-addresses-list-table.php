<?php
/**
 * Display generated addresses, their status and related orders.
 *
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway\Admin;

use BrianHenryIE\WC_Bitcoin_Gateway\API\Address_Storage\Crypto_Address;
use WP_Post;

/**
 * Hooks into standard WP_List_Table actions and filters.
 *
 * @see wp-admin/edit.php?post_type=bh-crypto-address
 * @see WP_Posts_List_Table
 */
class Addresses_List_Table {

	/**
	 * Cache to avoid repeatedly instantiating each Crypto_Address.
	 *
	 * @var array<int, Crypto_Address>
	 */
	protected array $data = array();

	/**
	 * Define the custom columns for the post type.
	 * Status|Order|Transactions|Balance|Wallet.
	 *
	 * TODO: Only show the wallet column if there is more than one wallet.
	 *
	 * @hooked manage_edit-bh-crypto-address_columns
	 * @see get_column_headers()
	 *
	 * @param array<string, string> $columns Column name : HTML output.
	 *
	 * @return array<string, string>
	 */
	public function define_columns( array $columns ): array {

		$new_columns = array();
		foreach ( $columns as $key => $column ) {

			// Omit the comments' column.
			if ( 'comments' === $key ) {
				continue;
			}

			// Add remaining columns after the Title column.
			$new_columns[ $key ] = $column;
			if ( 'title' === $key ) {

				$new_columns['status']               = 'Status';
				$new_columns['order_id']             = 'Order';
				$new_columns['transactions_count']   = 'Transactions';
				$new_columns['balance']              = 'Balance';
				$new_columns['wallet']               = 'Wallet';
				$new_columns['derive_path_sequence'] = 'Path';
			}
			// The date column will be added last.
		}

		return $new_columns;
	}

	/**
	 * Print the output for our custom columns.
	 *
	 * @hooked manage_bh-crypto-address_posts_custom_column
	 * @see \WP_Posts_List_Table::column_default()
	 *
	 * @param string $column_name The current column name.
	 * @param int    $post_id     The post whose row is being printed.
	 *
	 * @return void
	 */
	public function print_columns( string $column_name, int $post_id ): void {

		if ( ! isset( $this->data[ $post_id ] ) ) {
			try {
				$this->data[ $post_id ] = new Crypto_Address( $post_id );
			} catch ( \Exception $exception ) {
				// If the post_id isn't for a Crypto_Address.
				return;
			}
		}

		$crypto_address = $this->data[ $post_id ];

		switch ( $column_name ) {
			case 'status':
				echo esc_html( $crypto_address->get_status() );

				break;
			case 'order_id':
				$order_id = $crypto_address->get_order_id();
				if ( ! is_null( $order_id ) ) {
					$url = admin_url( "post.php?post={$order_id}&action=edit" );

					echo '<a href="' . esc_url( $url ) . '">' . esc_html( $order_id ) . '</a>';
				}

				break;
			case 'balance':
				// TODO: Show current balance or show in + out?
				echo esc_html( $crypto_address->get_balance() ?? 'unknown' );

				break;
			case 'transactions_count':
				$transactions = $crypto_address->get_transactions();
				if ( is_array( $transactions ) ) {
					echo count( $transactions );
				} else {
					echo '';
				}

				break;
			case 'wallet':
				$wallet_post_id = $crypto_address->get_wallet_parent_post_id();
				echo esc_html( $wallet_post_id );

				break;
			case 'derive_path_sequence':
				$nth  = $crypto_address->get_derivation_path_sequence_number();
				$path = "0/$nth";
				echo esc_html( $path );

				break;
			default:
				return;
		}

	}

	/**
	 * Remove edit and view actions, add an update action.
	 *
	 * TODO: add a click handler to the update action.
	 *
	 * @hooked post_row_actions
	 * @see \WP_Posts_List_Table::handle_row_actions()
	 *
	 * @param array<string,string> $actions Action id : HTML.
	 * @param WP_Post              $post     The post object.
	 *
	 * @return array<string,string>
	 */
	public function edit_row_actions( array $actions, WP_Post $post ): array {

		if ( Crypto_Address::POST_TYPE !== $post->post_type ) {
			return $actions;
		}

		unset( $actions['edit'] );
		unset( $actions['inline hide-if-no-js'] ); // "quick edit".
		unset( $actions['view'] );

		$actions['update_address'] = 'Update';

		return $actions;
	}

}
