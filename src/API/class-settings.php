<?php
/**
 * Object containing the plugin settings.
 *
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

namespace Nullcorps\WC_Gateway_Bitcoin\API;

use Nullcorps\WC_Gateway_Bitcoin\BrianHenryIE\WP_Logger\Logger_Settings_Trait;
use Nullcorps\WC_Gateway_Bitcoin\BrianHenryIE\WP_Logger\WooCommerce_Logger_Settings_Interface;
use Nullcorps\WC_Gateway_Bitcoin\Settings_Interface;
use Psr\Log\LogLevel;

/**
 * Plain object pulling setting from wp_options.
 */
class Settings implements Settings_Interface, WooCommerce_Logger_Settings_Interface {
	use Logger_Settings_Trait;

	/**
	 * The minimum severity of logs to record.
	 *
	 * TODO: Pull from settings.
	 *
	 * @see LogLevel
	 *
	 * @return string
	 */
	public function get_log_level(): string {
		return LogLevel::DEBUG;
	}

	/**
	 * Plugin name for use by the logger in friendly messages printed to WordPress admin UI.
	 *
	 * @see Logger
	 *
	 * @return string
	 */
	public function get_plugin_name(): string {
		return 'Bitcoin Gateway for WooCommerce';
	}

	/**
	 * The plugin slug is used by the logger in file and URL paths.
	 *
	 * @return string
	 */
	public function get_plugin_slug(): string {
		return 'nullcorps-wc-gateway-bitcoin';
	}

	/**
	 * The plugin basename is used by the logger to add the plugins page action link.
	 * (and maybe for PHP errors)
	 *
	 * @see Logger
	 *
	 * @return string
	 */
	public function get_plugin_basename(): string {
		return defined( 'NULLCORPS_WOOCOMMERCE_GATEWAY_BITCOIN_BASENAME' ) ? NULLCORPS_WOOCOMMERCE_GATEWAY_BITCOIN_BASENAME : 'nullcorps-wc-gateway-bitcoin/nullcorps-wc-gateway-bitcoin.php';
	}

	/**
	 * The plugin version, as used in caching JS and CSS assets.
	 *
	 * @return string
	 */
	public function get_plugin_version(): string {
		return defined( 'NULLCORPS_WOOCOMMERCE_GATEWAY_BITCOIN_VERSION' ) ? NULLCORPS_WOOCOMMERCE_GATEWAY_BITCOIN_VERSION : '1.3.3';
	}

	/**
	 * TODO: Just randomise?
	 *
	 * @return string
	 */
	public function get_api_preference(): string {
		return 'Blockchain.info'; // | 'Blockstream.info'
	}

	public function get_plugin_url(): string {
		return NULLCORPS_WOOCOMMERCE_GATEWAY_BITCOIN_URL;
	}
}
