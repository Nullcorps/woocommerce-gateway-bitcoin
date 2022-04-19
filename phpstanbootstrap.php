<?php
/**
 * Define constants that PhpStan cannot find.
 *
 * @see https://phpstan.org/user-guide/discovering-symbols#global-constants
 *
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

define( 'WP_CONTENT_DIR', __DIR__ . '/wp-content' );
define( 'WP_PLUGIN_DIR', __DIR__ . '/wp-content/plugins' );

define( 'NULLCORPS_WOOCOMMERCE_GATEWAY_BITCOIN_URL', 'http://localhost:8080/woocommerce-gateway-bitcoin' );
define( 'NULLCORPS_WOOCOMMERCE_GATEWAY_BITCOIN_PATH', __DIR__ );
