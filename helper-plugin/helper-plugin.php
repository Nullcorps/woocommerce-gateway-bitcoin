<?php
/**
 * Plugin Name:       Bitcoin Gateway Test Helper
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\Helper_Plugin;

// Add a "Customer Order Link" to the order admin page.

function order_link(): void {
	/** @var \WC_Order $wc_order */
	$wc_order = wc_get_order( absint( $_GET['post'] ) );
	$link     = $wc_order->get_checkout_order_received_url();

	$script = <<<EOT
jQuery('.woocommerce-order-data__heading').append('<span style="display: inline-block;"><a class="customer_order_link" title="Customer order link" target="_blank" href="$link">Customer Order Link</a></span>');
EOT;
	$style  = <<<EOT
.customer_order_link {
  color: #333; margin: 1.33em 0 0;
  width: 14px;
  height: 0;
  padding: 14px 0 0;
  margin: 0 0 0 6px;
  overflow: hidden;
  position: relative;
  color: #999;
  border: 0;
  float: right;
}
.customer_order_link::after {
  font-family: Dashicons;
  content: "\\f504";
  position: absolute;
  top: 0;
  left: 0;
  text-align: center;
  vertical-align: top;
  line-height: 14px;
  font-size: 14px;
  font-weight: 400;
}
EOT;

	echo '<script>' . $script . '</script>';
	echo '<style>' . $style . '</style>';
}
add_action(
	'init',
	function (): void {
		global $pagenow;
		if ( 'post.php' !== $pagenow ) {
			return;
		}
		$post_id = absint( $_GET['post'] );

		$post_type = get_post_type( $post_id );

		if ( 'shop_order' !== $post_type ) {
			return;
		}

		add_action( 'admin_footer', __NAMESPACE__ . '\order_link' );
	}
);
