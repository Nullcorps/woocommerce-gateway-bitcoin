<?php
/**
 * Add the payment instructions to the order page.
 *
 * @see woocommerce/templates/myaccount/view-order.php
 *
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce;

use BrianHenryIE\WC_Bitcoin_Gateway\API_Interface;
use Exception;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Order;

class My_Account_View_Order {
	use LoggerAwareTrait;

	protected API_Interface $api;

	const TEMPLATE_NAME = 'myaccount/view-order-bitcoin-instructions-status.php';

	public function __construct( API_Interface $api, LoggerInterface $logger ) {
		$this->setLogger( $logger );
		$this->api = $api;
	}

	/**
	 *
	 * @hooked woocommerce_view_order
	 */
	public function print_status_instructions( int $order_id ): void {

		if ( ! $this->api->is_order_has_bitcoin_gateway( $order_id ) ) {
			return;
		}

		/**
		 * This is definitely an order object. For it to be a Bitcoin order, it first must be an order.
		 *
		 * @var WC_Order $order
		 */
		$order = wc_get_order( $order_id );

		try {
			$template_args = $this->api->get_formatted_order_details( $order, false );
		} catch ( Exception $exception ) {
			// Exception occurs when an order has no Bitcoin address, e.g. if there was a problem fetching one as the
			// order was created.
			$this->logger->warning(
				"Failed to get `shop_order:{$order_id}` details for my-account template: {$exception->getMessage()}",
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
