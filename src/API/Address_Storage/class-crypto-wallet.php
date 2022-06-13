<?php
/**
 * Custom post type in WordPress, keyed with GUID of the wallet.
 *
 * TODO: Update the wp_post last modified time when updating metadata.
 *
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

namespace Nullcorps\WC_Gateway_Bitcoin\API\Address_Storage;

use Exception;
use WP_Post;

/**
 * Facade on WP_Post and post_meta.
 */
class Crypto_Wallet {

	const POST_TYPE = 'bh-crypto-wallet';

	const BALANCE_META_KEY                    = 'crypto_wallet_balance';
	const LAST_DERIVED_ADDRESS_INDEX_META_KEY = 'last_derived_address_index';

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
		return $this->post->post_content;
	}

	/**
	 * Get the current balance of this wallet, or null if it has never been checked.
	 *
	 * Must iterate across all addreses and sum them.
	 *
	 * @return ?string
	 */
	public function get_balance(): ?string {
		$balance = get_post_meta( $this->post->ID, self::BALANCE_META_KEY, true );
		return empty( $balance ) ? null : $balance;
	}

	/**
	 * Find addresses generated from this wallet which are unused and return them as `Crypto_Address` objects.
	 *
	 * TODO: Maybe this shouldn't be in here?
	 *
	 * @return Crypto_Address[]
	 */
	public function get_fresh_addresses(): array {
		$posts = get_posts(
			array(
				'post_parent'    => $this->post->ID,
				'post_type'      => Crypto_Address::POST_TYPE,
				'post_status'    => 'unused',
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'posts_per_page' => -1,
			)
		);
		return array_map(
			function( WP_Post $post ) {
				return new Crypto_Address( $post->ID );
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
