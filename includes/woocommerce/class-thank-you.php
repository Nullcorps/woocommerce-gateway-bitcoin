<?php
/**
 * Print the payment details on the Thank You / order-received page.
 *
 * TODO: JS to scroll to the payment details.
 *
 * @package    brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce;

use BrianHenryIE\WP_Bitcoin_Gateway\API_Interface;
use Exception;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Order;

/**
 * Get the order details and pass them to the thank you page template.
 */
class Thank_You {
	use LoggerAwareTrait;

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
	 * @param API_Interface   $api The main plugin functions.
	 * @param LoggerInterface $logger A PSR logger.
	 */
	public function __construct( API_Interface $api, LoggerInterface $logger ) {
		$this->setLogger( $logger );
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

		try {
			$template_args = $this->api->get_formatted_order_details( $order, false );
		} catch ( Exception $exception ) {
			// Exception sometimes occurs when an order has no Bitcoin address, although that's not likely the case here.
			$this->logger->warning(
				"Failed to get `shop_order:{$order_id}` details for Thank You template: {$exception->getMessage()}",
				array(
					'order_id'  => $order_id,
					'exception' => $exception,
				)
			);
			return;
		}

		$template_args['template'] = self::TEMPLATE_NAME;

		wc_get_template( self::TEMPLATE_NAME, $template_args );
	}
}
