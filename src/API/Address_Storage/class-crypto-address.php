<?php
/**
 *
 *
 * TODO: Update the wp_post last modified time when updating metadata.
 *
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

namespace Nullcorps\WC_Gateway_Bitcoin\API\Address_Storage;

use DateTimeInterface;
use Exception;
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
	 * @param int $post_id
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
	 * @return int The xpub|ypub|zpub wallet this address was derived for.
	 */
	public function get_wallet_parent_post_id(): int {
		return $this->post->post_parent;
	}

	/**
	 * @return ?int
	 */
	public function get_derivation_path_sequence_number(): ?int {
		$value = get_post_meta( $this->post->ID, self::DERIVATION_PATH_SEQUENCE_NUMBER_META_KEY, true );
		return is_numeric( $value ) ? intval( $value ) : null;
	}

	/**
	 * @return string
	 */
	public function get_raw_address(): string {
		return $this->post->post_excerpt;
	}

	/**
	 * @return array<string,array{txid:string, time:DateTimeInterface, value:string, confirmations:int}>|null
	 */
	public function get_transactions(): ?array {
		$value = get_post_meta( $this->post->ID, self::TRANSACTION_META_KEY, true );
		return is_array( $value ) ? $value : null;
	}

	/**
	 * Save the transactions recently fetched from the API.
	 *
	 * @param array<string,array{txid:string, time:DateTimeInterface, value:string, confirmations:int}> $refreshed_transactions
	 *
	 * @return void
	 */
	public function set_transactions( array $refreshed_transactions ): void {

		wp_update_post(
			array(
				'ID'         => $this->post->ID,
				'meta_input' => array(
					self::TRANSACTION_META_KEY => $refreshed_transactions,
				),
			)
		);

		if ( empty( $refreshed_transactions ) ) {
			$this->set_status( 'unused' );
		} elseif ( 'unknown' === $this->get_status() ) {
			$this->set_status( 'used' );
		}
	}

	/**
	 * @return string
	 */
	public function get_balance(): string {
		return get_post_meta( $this->post->ID, self::BALANCE_META_KEY, true );
	}

	/**
	 * * unknown: probably brand new and unchecked
	 * * unused: new and no order id assigned
	 * * assigned: assigned to an order, payment incomplete
	 * * used: transactions present and no order id, or and order id assigned and payment complete
	 *
	 * @return string unknown|unused|assigned|used
	 */
	public function get_status(): string {

		return $this->post->post_status;
	}

	/**
	 * Set the current status of the address.
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

		wp_update_post(
			array(
				'ID'         => $this->post->ID,
				'meta_input' => array(
					self::ORDER_ID_META_KEY => $order_id,
				),
			)
		);

		if ( 'assigned' !== $this->get_status() ) {
			$this->set_status( 'assigned' );
		}
	}

}
