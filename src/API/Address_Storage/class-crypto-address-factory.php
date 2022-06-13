<?php
/**
 *
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

namespace Nullcorps\WC_Gateway_Bitcoin\API\Address_Storage;

use Exception;
use wpdb;

class Crypto_Address_Factory {

	/**
	 * Given a post_id,
	 *
	 * @param string $address
	 *
	 * @return int|null
	 */
	public function get_post_id_for_address( string $address ): ?int {

		$post_id = wp_cache_get( $address, Crypto_Address::POST_TYPE );

		if ( false !== $post_id ) {
			return (int) $post_id;
		}

		/** @var wpdb $wpdb */
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// @phpstan-ignore-next-line
		$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name=%s", sanitize_title( $address ) ) );

		if ( ! is_null( $post_id ) ) {
			$post_id = intval( $post_id );
			wp_cache_add( $address, $post_id, Crypto_Address::POST_TYPE );
		}

		return $post_id;
	}

	/**
	 * Wrapper on wp_insert_post(), sets the address as the post_title, post_excerpt and post_name.
	 *
	 * @param string        $address The Bitcoin address.
	 * @param int           $address_index
	 * @param Crypto_Wallet $wallet The wallet whose xpub this address was derived from.
	 *
	 * @return int The new post_id.
	 *
	 * @throws Exception When WordPress fails to create the wp_post.
	 */
	public function save_new( string $address, int $address_index, Crypto_Wallet $wallet ): int {

		// TODO: Validate address, throw exception.

		$args = array(
			'post_type'    => Crypto_Address::POST_TYPE,
			'post_name'    => sanitize_title( $address ), // An indexed column.
			'post_excerpt' => $address,
			'post_title'   => $address,
			'post_status'  => 'unknown',
			'post_parent'  => $wallet->get_post_id(),
			'meta_input'   => array(
				Crypto_Address::DERIVATION_PATH_SEQUENCE_NUMBER_META_KEY => $address_index,
			),
		);

		$post_id = wp_insert_post( $args );

		if ( is_wp_error( $post_id ) ) {
			// TODO Log.
			throw new Exception( 'WordPress failed to create a post for the wallet.' );
		}

		// TODO: Maybe start a background job to check for transactions. Where is best to do that?

		return $post_id;
	}

	/**
	 * Given the id of the wp_posts row storing the crypto address, return the typed Crypto_Address object.
	 *
	 * @param int $post_id WordPress wp_posts ID.
	 *
	 * @return Crypto_Address
	 * @throws Exception When the post_type of the post returned for the given post_id is not a Crypto_Address.
	 */
	public function get_by_post_id( int $post_id ): Crypto_Address {
		return new Crypto_Address( $post_id );
	}

}
