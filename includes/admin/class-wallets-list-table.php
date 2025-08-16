<?php
/**
 * Display wallets in use/formerly in use, their status, balance
 *
 * @package    brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\Admin;

use BrianHenryIE\WP_Bitcoin_Gateway\API_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Wallet;
use WP_Post;

/**
 * Hooks into standard WP_List_Table actions and filters.
 *
 * @see wp-admin/edit.php?post_type=bh-bitcoin-wallet
 * @see WP_Posts_List_Table
 */
class Wallets_List_Table extends \WP_Posts_List_Table {

	/**
	 * The main plugin functions.
	 *
	 * Not in use here currently.
	 */
	protected API_Interface $api;

	/**
	 * Constructor
	 *
	 * @see _get_list_table()
	 *
	 * @param array{screen?:\WP_Screen} $args The data passed by WordPress.
	 */
	public function __construct( $args = array() ) {
		parent::__construct( $args );

		$post_type_name = $this->screen->post_type;

		/**
		 * Since this object is instantiated because it was defined when registering the post type, it's
		 * extremely unlikely the post type will not exist.
		 *
		 * @var \WP_Post_Type $post_type_object
		 */
		$post_type_object = get_post_type_object( $post_type_name );
		$this->api        = $post_type_object->plugin_objects['api'];

		add_filter( 'post_row_actions', array( $this, 'edit_row_actions' ), 10, 2 );
	}

	/**
	 * Define the custom columns for the post type.
	 * Status|Balance.
	 *
	 * TODO: Only show the wallet column if there is more than one wallet.
	 *
	 * @return array<string, string> Column name : HTML output.
	 */
	public function get_columns() {
		$columns = parent::get_columns();

		$new_columns = array();
		foreach ( $columns as $key => $column ) {

			// Omit the "comments" column.
			if ( 'comments' === $key ) {
				continue;
			}

			// Add remaining columns after the Title column.
			$new_columns[ $key ] = $column;
			if ( 'title' === $key ) {

				$new_columns['status']  = 'Status';
				$new_columns['balance'] = 'Balance';
			}
			// The date column will be added last.
		}

		return $new_columns;
	}

	/**
	 * Cache each Bitcoin_Wallet object between calls to each `print_{$column}()`.
	 *
	 * @var array<int, Bitcoin_Wallet>
	 */
	protected array $wallets_cache = array();

	/**
	 * Fill or retrieve from the above cache of Wallet objects.
	 *
	 * @param WP_Post $post The post object for the current row.
	 *
	 * @throws \Exception When the post is not a `bh-bitcoin-wallet` post type.
	 */
	protected function get_cached_bitcoin_wallet_object( WP_Post $post ): Bitcoin_Wallet {
		if ( ! isset( $this->wallets_cache[ $post->ID ] ) ) {
			$this->wallets_cache[ $post->ID ] = new Bitcoin_Wallet( $post->ID );
		}
		return $this->wallets_cache[ $post->ID ];
	}

	/**
	 * Print the status of this wallet.
	 *
	 * One of active|inactive.
	 *
	 * @see Post::register_wallet_post_type()
	 *
	 * @param WP_Post $post The post this row is being rendered for.
	 */
	public function column_status( WP_Post $post ): void {
		$bitcoin_wallet = $this->get_cached_bitcoin_wallet_object( $post );

		echo esc_html( $bitcoin_wallet->get_status() );
	}

	/**
	 * Print the total Bitcoin received by this wallet.
	 *
	 * TODO: Not yet implemented.
	 *
	 * @param WP_Post $post The post this row is being rendered for.
	 */
	public function column_balance( WP_Post $post ): void {
		$bitcoin_wallet = $this->get_cached_bitcoin_wallet_object( $post );

		echo esc_html( $bitcoin_wallet->get_balance() ?? 'unknown' );
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
