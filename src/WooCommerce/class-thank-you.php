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

	/**
	 * Used to check is the gateway relevant for this thank you page load.
	 *
	 * @uses API_Interface::is_order_has_bitcoin_gateway()
	 *
	 * @var API_Interface
	 */
	protected API_Interface $api;

	/**
	 * Constructor
	 *
	 * @param API_Interface $api The main plugin functions.
	 */
	public function __construct( API_Interface $api ) {
		$this->api = $api;
	}

	/**
	 * When the thank you page loads, if the order loading is a Bitcoin order, print the payment instructions (via
	 * the template).
	 *
	 * @hooked woocommerce_thankyou
	 *
	 * @param int $order_id The order if of the (presumably new) order.
	 *
	 * @return void Prints its output.
	 */
	public function print_instructions( int $order_id ): void {

		if ( ! $this->api->is_order_has_bitcoin_gateway( $order_id ) ) {
			return;
		}

		/**
		 * No need to check again does `wc_get_order()` return a `WC_Order` object because `API::is_order_has_bitcoin_gateway()`
		 * already has.
		 *
		 * @var WC_Order $order
		 */
		$order = wc_get_order( $order_id );

		$order_details = $this->api->get_formatted_order_details( $order, false );

		wc_get_template( self::TEMPLATE_NAME, $order_details );

	}

}
