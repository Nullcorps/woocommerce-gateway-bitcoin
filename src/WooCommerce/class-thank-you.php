<?php
/**
 *
 * TODO: JS to scroll to the payment details.
 *
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

namespace Nullcorps\WC_Gateway_Bitcoin\WooCommerce;

use Nullcorps\WC_Gateway_Bitcoin\API\API_Interface;
use WC_Order;

class Thank_You {
	const TEMPLATE_NAME = 'checkout/thankyou-bitcoin-instructions-status.php';

	protected API_Interface $api;

	/**
	 *
	 * @param API_Interface $api The main plugin functions.
	 */
	public function __construct( API_Interface $api ) {
		$this->api = $api;
	}

	/**
	 *
	 * @hooked woocommerce_thankyou
	 *
	 * @param int $order_id
	 *
	 * @return void
	 */
	public function print_instructions( int $order_id ): void {

		if ( ! $this->api->is_order_has_bitcoin_gateway( $order_id ) ) {
			return;
		}

		/** @var WC_Order $order */
		$order = wc_get_order( $order_id );

		$order_details = $this->api->get_order_details( $order, false );

		wc_get_template( 'checkout/thankyou-bitcoin-instructions-status.php', $order_details );

	}

}
