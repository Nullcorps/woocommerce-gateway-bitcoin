<?php
/**
 *
 * TODO: Prevent sending the on-hold email immediately, reschedule it for one hour later.
 *
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

namespace Nullcorps\WC_Gateway_Bitcoin\WooCommerce;

use Nullcorps\WC_Gateway_Bitcoin\API\API_Interface;
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
			// Not a Bitcoin gateway.
			return;
		}

		$order_details = $this->api->get_order_details( $order, false );

		wc_get_template( 'emails/email-bitcoin-instructions-status.php', $order_details );

	}

}
