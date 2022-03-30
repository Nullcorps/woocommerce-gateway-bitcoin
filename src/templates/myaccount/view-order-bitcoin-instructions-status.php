<?php
/**
 * @package    nullcorps/woocommerce-gateway-bitcoin
 *
 * @var array<string, mixed> $args The full array of data passed to the template function.
 * @var WC_Order $order The order that Bitcoin is being used to pay.
 */

if ( ! $order->is_paid() ) {

	wc_get_template( 'bitcoin-unpaid.php', $args );

} else {

	wc_get_template( 'bitcoin-paid.php', $args );
}
