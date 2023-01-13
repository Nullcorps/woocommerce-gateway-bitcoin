<?php
/**
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway\Frontend;

use BrianHenryIE\WC_Bitcoin_Gateway\API_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class AJAX {
	use LoggerAwareTrait;

	protected API_Interface $api;

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

		// TODO: Include the order key in the AJAX request
		// if( $order->get_customer_id() !== get_current_user_id() && ! $order->key_is_valid( $key ) ) {
		// wp_send_json_error( 'Not permitted', 401 );
		// }

		$result = $this->api->get_formatted_order_details( $order, true );

		wp_send_json_success( $result );

	}
}
