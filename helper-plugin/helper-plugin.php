<?php
/**
 * Plugin Name:       Bitcoin Gateway Test Helper
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\Helper_Plugin;

// TODO check for stray requests
// https://api-pub.bitfinex.com/v2/tickers?symbols=tBTCUSD

// wp-env cron fix.

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	$hostname = gethostname();
	update_option( 'wp_env_cron_hostname', $hostname );
}

/**
 * @see WP_Http::request()
 */
add_filter(
	'http_request_args',
	function ( $a ) {
		return $a;
	}
);

/**
 * @see get_site_url()
 * @see cron.php:957
 */
add_filter(
	'site_url',
	function ( $url, $path, ) {
		if ( 'wp-cron.php' === $path ) {
			return 'http://' . get_option( 'wp_env_cron_hostname' ) . '/wp-cron.php';
		}
		if ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) {
			return 'http://' . get_option( 'wp_env_cron_hostname' );
		}
		return $url;
	},
	10,
	2
);


// Add a "Customer Order Link" to the order admin page.

/**
 * @hooked admin_footer
 */
function order_link(): void {
	global $pagenow;
	if ( 'post.php' !== $pagenow ) {
		return;
	}
	if ( ! isset( $_GET['post'] ) ) {
		return;
	}
	$post_id = absint( $_GET['post'] );

	$post_type = get_post_type( $post_id );

	if ( 'shop_order' !== $post_type ) {
		return;
	}

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
add_action( 'admin_footer', __NAMESPACE__ . '\order_link' );
