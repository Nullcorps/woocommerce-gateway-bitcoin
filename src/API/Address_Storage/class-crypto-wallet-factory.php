<?php
/**
 *
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway\API\Address_Storage;

use Exception;
use wpdb;


class Crypto_Wallet_Factory {

	/**
	 * Given a post_id,
	 *
	 * NB: post_name is 200 characters long. zpub is 111 characters.
	 *
	 * @param string $xpub
	 *
	 * @return int|null
	 */
	public function get_post_id_for_wallet( string $xpub ): ?int {

		$post_id = wp_cache_get( $xpub, Crypto_Wallet::POST_TYPE );

		if ( false !== $post_id ) {
			return (int) $post_id;
		}

		/** @var wpdb $wpdb */
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// @phpstan-ignore-next-line
		$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name=%s", sanitize_title( $xpub ) ) );

		if ( ! is_null( $post_id ) ) {
			$post_id = intval( $post_id );
			wp_cache_add( $xpub, $post_id, Crypto_Wallet::POST_TYPE );
		}

		return $post_id;
	}


	public function save_new( string $xpub, ?string $gateway_id ): int {

		// TODO: Validate xpub, throw exception.

		$args = array();

		$args['post_status']  = ! is_null( $gateway_id ) ? 'active' : 'inactive';
		$args['post_excerpt'] = $xpub;
		$args['post_name']    = sanitize_title( $xpub ); // An indexed column.
		$args['post_type']    = Crypto_Wallet::POST_TYPE;

		$post_id = wp_insert_post( $args, true );

		if ( is_wp_error( $post_id ) ) {
			throw new \Exception( 'Failed to save new wallet as wp_post' );
		}

		return $post_id;
	}

	/**
	 * Given the id of the wp_posts row storing the crypto wallet, return the typed Crypto_Wallet object.
	 *
	 * @param int $post_id WordPress wp_posts ID.
	 *
	 * @return Crypto_Wallet
	 * @throws Exception When the post_type of the post returned for the given post_id is not a Crypto_Address.
	 */
	public function get_by_post_id( int $post_id ): Crypto_Wallet {
		return new Crypto_Wallet( $post_id );
	}

}
