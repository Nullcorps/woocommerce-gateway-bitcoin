<?php
/**
 * Display wallets in use/formerly in use, their status, balance
 *
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway\Admin;

use Exception;
use BrianHenryIE\WC_Bitcoin_Gateway\API\Addresses\Bitcoin_Wallet;
use WP_Post;

/**
 * Hooks into standard WP_List_Table actions and filters.
 *
 * @see wp-admin/edit.php?post_type=bh-bitcoin-wallet
 * @see WP_Posts_List_Table
 */
class Wallets_List_Table {

	/**
	 * Cache each Bitcoin_Wallet object between calls to each column in print_columns().
	 *
	 * @var array<int, Bitcoin_Wallet>
	 */
	protected array $data = array();

	/**
	 * Define the custom columns for the post type.
	 * Status|Balance.
	 *
	 * TODO: Only show the wallet column if there is more than one wallet.
	 *
	 * @hooked manage_edit-bh-bitcoin-address_columns
	 * @see get_column_headers()
	 *
	 * @param array<string, string> $columns Column name : HTML output.
	 *
	 * @return array<string, string>
	 */
	public function define_columns( array $columns ): array {

		$new_columns = array();
		foreach ( $columns as $key => $column ) {

			if ( 'comments' === $key ) {
				continue;
			}

			$new_columns[ $key ] = $column;
			if ( 'title' === $key ) {

				$new_columns['status']  = 'Status';
				$new_columns['balance'] = 'Balance';
			}
		}

		return $new_columns;
	}

	/**
	 * Print the output for our custom columns.
	 *
	 * @hooked manage_bh-bitcoin-wallet_posts_custom_column
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
				$this->data[ $post_id ] = new Bitcoin_Wallet( $post_id );
			} catch ( Exception $exception ) {
				// Not a Bitcoin_Wallet. Unlikely to ever reach here.
				return;
			}
		}

		$bitcoin_wallet = $this->data[ $post_id ];

		switch ( $column_name ) {
			case 'status':
				echo esc_html( $bitcoin_wallet->get_status() );
				break;

			case 'balance':
				echo esc_html( $bitcoin_wallet->get_balance() ?? 'unknown' );
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
	 * @param WP_Post              $post    The post object.
	 *
	 * @return array<string,string>
	 */
	public function edit_row_actions( array $actions, WP_Post $post ): array {

		if ( Bitcoin_Wallet::POST_TYPE !== $post->post_type ) {
			return $actions;
		}

		unset( $actions['edit'] );
		unset( $actions['inline hide-if-no-js'] ); // "quick edit".
		unset( $actions['view'] );

		$actions['update_address'] = 'Update';

		return $actions;
	}

}
