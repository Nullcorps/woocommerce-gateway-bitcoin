<?php
/**
 * AJAX endpoint for fetching order information.
 *
 * Used on Thank You and my-account screens to query for transaction updates.
 *
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway\Frontend;

use BrianHenryIE\WC_Bitcoin_Gateway\API_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Action `bh_wc_bitcoin_gateway_refresh_order_details` hooked to `wp_ajax` and `wp_ajax_nopriv`.
 */
class AJAX {
	use LoggerAwareTrait;

	/**
	 * Main class to get order information.
	 *
	 * @uses API_Interface::get_formatted_order_details()
	 */
	protected API_Interface $api;

	/**
	 * Constructor
	 *
	 * @param API_Interface   $api The main plugin functions.
	 * @param LoggerInterface $logger A PSR logger.
	 */
	public function __construct( API_Interface $api, LoggerInterface $logger ) {
		$this->setLogger( $logger );
		$this->api = $api;
	}

	/**
	 * Return data for number of confirmations,
	 * is the order paid.
	 * does more need to be sent
	 *
	 * @hooked wp_ajax_bh_wc_bitcoin_gateway_refresh_order_details
	 *
	 * @return void
	 */
	public function get_order_details() {

		if ( ! check_ajax_referer( Frontend_Assets::class, false, false ) ) {
			wp_send_json_error( array( 'message' => 'Bad/no nonce.' ), 400 );
		}

		if ( ! isset( $_POST['order_id'] ) ) {
			wp_send_json_error( 'No order id provided.', 400 );
		}

		$order_id = intval( wp_unslash( $_POST['order_id'] ) );

		$order = wc_get_order( $order_id );

		if ( ! ( $order instanceof \WC_Order ) ) {
			wp_send_json_error( 'Invalid order id', 400 );
		}

		// TODO: Include the order key in the AJAX request.
		// if( $order->get_customer_id() !== get_current_user_id() && ! $order->key_is_valid( $key ) ) {
		// wp_send_json_error( 'Not permitted', 401 );
		// }

		$result = $this->api->get_formatted_order_details( $order, true );

		// These are the only keys used by the JavaScript.
		$allowed_keys = array(
			'btc_address',
			'btc_total',
			'order_id',
			'btc_amount_received',
			'status',
			'amount_received',
			'order_status_formatted',
			'last_checked_time_formatted',
		);

		foreach ( array_keys( $result ) as $key ) {
			if ( ! in_array( $key, $allowed_keys, true ) ) {
				unset( $result[ $key ] );
			}
		}

		wp_send_json_success( $result );

	}
}
