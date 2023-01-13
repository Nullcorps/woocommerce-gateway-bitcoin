<?php
/**
 *
 *
 * TODO: Update the wp_post last modified time when updating metadata.
 *
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway\API\Address_Storage;

use DateTimeInterface;
use Exception;
use BrianHenryIE\WC_Bitcoin_Gateway\Admin\Addresses_List_Table;
use BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\Bitcoin_Gateway;
use tad\WPBrowser\Generators\Date;
use WP_Post;

/**
 * Facade on WP_Post and post_meta.
 */
class Crypto_Address {

	const POST_TYPE = 'bh-crypto-address';

	const TRANSACTION_META_KEY                     = 'address_transactions';
	const DERIVATION_PATH_SEQUENCE_NUMBER_META_KEY = 'derivation_path_sequence_number';
	const BALANCE_META_KEY                         = 'balance';
	const ORDER_ID_META_KEY                        = 'order_id';

	/**
	 * The wp_post database row, as a WordPress post object, for the custom post type used to store the data.
	 *
	 * @var WP_Post
	 */
	protected WP_Post $post;

	/**
	 * Constructor
	 *
	 * @param int $post_id The wp_post ID the Bitcoin address detail is stored under.
	 *
	 * @throws Exception When the supplied post_id is not a post of this type.
	 */
	public function __construct( int $post_id ) {
		$post = get_post( $post_id );
		if ( ! ( $post instanceof WP_Post ) || self::POST_TYPE !== $post->post_type ) {
			throw new Exception( 'post_id ' . $post_id . ' is not a ' . self::POST_TYPE . ' post object' );
		}

		$this->post = $post;
	}

	/**
	 * The post ID for the xpub|ypub|zpub wallet this address was derived for.
	 *
	 * @return int
	 */
	public function get_wallet_parent_post_id(): int {
		return $this->post->post_parent;
	}

	/**
	 * Get this Bitcoin address's derivation path.
	 *
	 * @return ?int
	 */
	public function get_derivation_path_sequence_number(): ?int {
		$value = get_post_meta( $this->post->ID, self::DERIVATION_PATH_SEQUENCE_NUMBER_META_KEY, true );
		return is_numeric( $value ) ? intval( $value ) : null;
	}

	/**
	 * Return the raw Bitcoin address this object represents.
	 *
	 * @used-by API::check_new_addresses_for_transactions() When verifying newly generated addresses have no existing transactions.
	 * @used-by API::get_fresh_address_for_order() When adding the payment address to the order meta.
	 * @used-by WC_Bitcoin_Gateway::process_payment() When adding a link in the order notes to view transactions on a 3rd party website.
	 * @used-by API::query_api_for_address_transactions() When checking has an order been paid.
	 */
	public function get_raw_address(): string {
		return $this->post->post_excerpt;
	}

	/**
	 * Return the previously saved transactions for this address.
	 *
	 * @used-by API::query_api_for_address_transactions() When checking previously fetched transactions before a new query.
	 * @used-by API::get_order_details() When displaying the order/address details in the admin/frontend UI.
	 * @used-by Addresses_List_Table::print_columns() When displaying all addresses.
	 *
	 * @return array<string,array{txid:string, time:DateTimeInterface, value:string, confirmations:int}>|null
	 */
	public function get_transactions(): ?array {
		$value = get_post_meta( $this->post->ID, self::TRANSACTION_META_KEY, true );
		return is_array( $value ) ? $value : null;
	}

	/**
	 * Save the transactions recently fetched from the API.
	 *
	 * @used-by API::query_api_for_address_transactions()
	 *
	 * @param array<string,array{txid:string, time:DateTimeInterface, value:string, confirmations:int}> $refreshed_transactions Array of the transaction details keyed by each transaction id.
	 *
	 * @return void
	 */
	public function set_transactions( array $refreshed_transactions ): void {

		$update = array(
			'ID'         => $this->post->ID,
			'meta_input' => array(
				self::TRANSACTION_META_KEY => $refreshed_transactions,
			),
		);

		if ( empty( $refreshed_transactions ) ) {
			$update['post_status'] = 'unused';
		} elseif ( 'unknown' === $this->get_status() ) {
			$update['post_status'] = 'used';
		}

		wp_update_post( $update );
	}

	/**
	 * Return the balance saved in the post meta, or null if the address status is unknown.
	 *
	 * TODO: Might need a $confirmations parameter and calculate the balance from the transactions.
	 *
	 * @used-by Addresses_List_Table::print_columns()
	 *
	 * @return ?string Null if unknown.
	 */
	public function get_balance(): ?string {
		$balance = get_post_meta( $this->post->ID, self::BALANCE_META_KEY, true );
		if ( empty( $balance ) ) {
			$balance = '0.0';
		}

		return 'unknown' === $this->get_status() ? null : $balance;
	}

	/**
	 * Return the current status of the Bitcoin address object. One of:
	 * * unknown: probably brand new and unchecked
	 * * unused: new and no order id assigned
	 * * assigned: assigned to an order, payment incomplete
	 * * used: transactions present and no order id, or and order id assigned and payment complete
	 *
	 * TODO: Check the saved status is valid.
	 *
	 * @return string unknown|unused|assigned|used.
	 */
	public function get_status(): string {

		return $this->post->post_status;
	}

	/**
	 * Set the current status of the address.
	 *
	 * Valid statuses: unknown|unused|assigned|used.
	 *
	 * TODO: Throw an exception if an invalid status is set. Maybe in the `wp_insert_post_data` filter.
	 * TODO: Maybe throw an exception if the update fails.
	 *
	 * @param string $status Status to assign.
	 */
	public function set_status( string $status ): void {

		wp_update_post(
			array(
				'post_type'   => self::POST_TYPE,
				'ID'          => $this->post->ID,
				'post_status' => $status,
			)
		);
	}

	/**
	 * Get the order id associated with this address, or null if none has ever been assigned.
	 *
	 * @return ?int
	 */
	public function get_order_id(): ?int {
		$value = intval( get_post_meta( $this->post->ID, self::ORDER_ID_META_KEY, true ) );
		return 0 === $value ? null : $value;
	}

	/**
	 * Add order_id metadata to the crypto address and update the status to assigned.
	 *
	 * @param int $order_id The WooCommerce order id the address is being used for.
	 */
	public function set_order_id( int $order_id ): void {

		$update = array(
			'ID'         => $this->post->ID,
			'meta_input' => array(
				self::ORDER_ID_META_KEY => $order_id,
			),
		);

		if ( 'assigned' !== $this->get_status() ) {
			$update['post_status'] = 'assigned';
		}

		wp_update_post( $update );
	}

}
