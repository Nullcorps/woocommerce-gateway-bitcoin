<?php
/**
 * Template to display either unpaid instructions or paid summary on the Thank You order confirmation page.
 *
 * @see \Nullcorps\WC_Gateway_Bitcoin\API_Interface::get_order_details()
 *
 * @var array<string, mixed> $args The full array of data passed to the template function.
 * @var WC_Order $order The order that Bitcoin is being used to pay.
 *
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

if ( ! $order->is_paid() ) {

	wc_get_template( 'bitcoin-unpaid.php', $args );

} else {

	wc_get_template( 'bitcoin-paid.php', $args );
}
