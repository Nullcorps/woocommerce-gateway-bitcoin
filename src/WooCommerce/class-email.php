<?php
/**
 *
 * TODO: Prevent sending the on-hold email immediately, reschedule it for one hour later.
 *
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

namespace Nullcorps\WC_Gateway_Bitcoin\WooCommerce;

use Nullcorps\WC_Gateway_Bitcoin\API_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Order;

class Email {
	use LoggerAwareTrait;

	const TEMPLATE_NAME = 'emails/email-bitcoin-instructions-status.php';

	protected API_Interface $api;

	public function __construct( API_Interface $api, LoggerInterface $logger ) {
		$this->setLogger( $logger );
		$this->api = $api;
	}

	/**
	 * Add payment instructions or payment status (once paid) to the WC emails.
	 *
	 * @hooked woocommerce_email_before_order_table
	 *
	 * @param WC_Order $order The order object the email is being sent for.
	 * @param bool     $sent_to_admin Is this email being sent to an admin, or a customer.
	 * @param bool     $plain_text Is this plain text email ?( !HTML email ).
	 */
	public function print_instructions( WC_Order $order, bool $sent_to_admin, bool $plain_text = false ): void {

		if ( $sent_to_admin ) {
			// TODO: Think about what information should be in admin emails.
			return;
		}

		if ( ! $this->api->is_bitcoin_gateway( $order->get_payment_method() ) ) {
			return;
		}

		/**
		 * There was an error where seemingly the order object being passed to this function is older than the
		 * one saved in `WC_Gateway_Bitcoin::process_payment()` and the meta was not present, so let's refresh.
		 *
		 * @var WC_Order $order
		 */
		$order = wc_get_order( $order->get_id() );

		try {
			$order_details = $this->api->get_formatted_order_details( $order, false );
		} catch ( \Exception $exception ) {
			$this->logger->error( 'Failed to get order details for email templates: ' . $exception->getMessage(), array( 'exception' => $exception ) );
			return;
		}

		// TODO: Create a plain text template.
		wc_get_template( self::TEMPLATE_NAME, $order_details );

	}

}
