<?php
/**
 * Custom post type in WordPress, keyed with GUID of the wallet.
 *
 * TODO: Update the wp_post last modified time when updating metadata.
 *
 * @package    brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses;

use Exception;
use WP_Post;

/**
 * Facade on WP_Post and post_meta.
 */
class Bitcoin_Wallet {

	const POST_TYPE = 'bh-bitcoin-wallet';

	/**
	 * TODO: We are not yet setting the balance.
	 */
	const BALANCE_META_KEY                    = 'bitcoin_wallet_balance';
	const LAST_DERIVED_ADDRESS_INDEX_META_KEY = 'last_derived_address_index';

	/**
	 * Meta key to store the payment gateway ids this wallet is used with.
	 * `get_post_meta( $wallet_post_id, 'payment_gateway_ids', false )` returns an array of gateway ids.
	 */
	const GATEWAY_IDS_META_KEY = 'payment_gateway_ids';

	/**
	 * The actual data as retrieved by WordPress from the database.
	 *
	 * @var WP_Post
	 */
	protected WP_Post $post;

	/**
	 * Constructor
	 *
	 * @param int $post_id The WordPress post id this wallet is stored under.
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
	 * Used when adding this wallet as a parent of a generated address.
	 *
	 * @return int
	 */
	public function get_post_id(): int {
		return $this->post->ID;
	}

	/**
	 * The current status of the wallet.
	 *
	 * TODO: Mark wallets inactive when removed from a gateway.
	 *
	 * @return string active|inactive
	 */
	public function get_status(): string {
		return $this->post->post_status;
	}

	/**
	 * Return the xpub/ypub/zpub this wallet represents.
	 *
	 * @return string
	 */
	public function get_xpub(): string {
		return $this->post->post_excerpt;
	}

	/**
	 * Get the current balance of this wallet, or null if it has never been checked.
	 *
	 * Must iterate across all addresses and sum them.
	 *
	 * @return ?string
	 */
	public function get_balance(): ?string {
		$balance = get_post_meta( $this->post->ID, self::BALANCE_META_KEY, true );
		return empty( $balance ) ? null : $balance;
	}

	/**
	 * Find addresses generated from this wallet which are unused and return them as `Bitcoin_Address` objects.
	 *
	 * TODO: Maybe this shouldn't be in here?
	 *
	 * @return Bitcoin_Address[]
	 */
	public function get_fresh_addresses(): array {
		$posts = get_posts(
			array(
				'post_parent'    => $this->post->ID,
				'post_type'      => Bitcoin_Address::POST_TYPE,
				'post_status'    => Bitcoin_Address_Status::UNUSED->value,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'posts_per_page' => -1,
			)
		);
		return array_map(
			function ( WP_Post $post ) {
				return new Bitcoin_Address( $post->ID );
			},
			$posts
		);
	}

	/**
	 * Get the index of the last generated address, so generating new addresses can start higher.
	 *
	 * @return int
	 */
	public function get_address_index(): int {
		$index = get_post_meta( $this->post->ID, self::LAST_DERIVED_ADDRESS_INDEX_META_KEY, true );
		return intval( $index ); // Empty string '' will parse to 0.
	}

	/**
	 * Save the index of the highest generated address.
	 *
	 * @param int $index Nth address generated index.
	 */
	public function set_address_index( int $index ): void {
		update_post_meta( $this->post->ID, self::LAST_DERIVED_ADDRESS_INDEX_META_KEY, $index );
	}
}
