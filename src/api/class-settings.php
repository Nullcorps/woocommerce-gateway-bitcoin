<?php
/**
 * Object containing the plugin settings.
 *
 * @package    brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\API;

use BrianHenryIE\WP_Bitcoin_Gateway\Admin\Plugins_Page;
use BrianHenryIE\WP_Bitcoin_Gateway\Frontend\Frontend_Assets;
use BrianHenryIE\WP_Bitcoin_Gateway\WP_Logger\Logger_Settings_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\WP_Logger\Logger_Settings_Trait;
use BrianHenryIE\WP_Bitcoin_Gateway\WP_Logger\WooCommerce_Logger_Settings_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\Settings_Interface;
use Psr\Log\LogLevel;

/**
 * Plain object pulling setting from wp_options.
 */
class Settings implements Settings_Interface, WooCommerce_Logger_Settings_Interface {
	use Logger_Settings_Trait;

	/**
	 * The minimum severity of logs to record.
	 *
	 * @see LogLevel
	 *
	 * @return string
	 */
	public function get_log_level(): string {
		$gateway_id     = 'bitcoin_gateway';
		$saved_settings = get_option( 'woocommerce_' . $gateway_id . '_settings', array() );
		$log_levels     = array( LogLevel::DEBUG, LogLevel::INFO, LogLevel::ERROR, LogLevel::NOTICE, LogLevel::WARNING, 'none' );
		$level          = $saved_settings['log_level'] ?? 'info';
		return in_array( $level, $log_levels, true ) ? $level : 'info';
	}

	/**
	 * Plugin name for use by the logger in friendly messages printed to WordPress admin UI.
	 *
	 * @see Logger
	 *
	 * @return string
	 */
	public function get_plugin_name(): string {
		return 'Bitcoin Gateway';
	}

	/**
	 * The plugin slug is used by the logger in file and URL paths.
	 *
	 * @return string
	 */
	public function get_plugin_slug(): string {
		return 'bh-wp-bitcoin-gateway';
	}

	/**
	 * Used to add links on plugins.php.
	 *
	 * @used-by Plugins_Page
	 *
	 * @see Logger_Settings_Interface::get_plugin_basename()
	 *
	 * @return string
	 */
	public function get_plugin_basename(): string {
		return defined( 'BH_WP_BITCOIN_GATEWAY_BASENAME' ) ? BH_WP_BITCOIN_GATEWAY_BASENAME : 'bh-wp-bitcoin-gateway/bh-wp-bitcoin-gateway.php';
	}

	/**
	 * The plugin version, as used in caching JS and CSS assets.
	 *
	 * @return string
	 */
	public function get_plugin_version(): string {
		return defined( 'BH_WP_BITCOIN_GATEWAY_VERSION' ) ? BH_WP_BITCOIN_GATEWAY_VERSION : '2.0.0';
	}

	/**
	 * Return the URL of the base of the plugin.
	 *
	 * @used-by Frontend_Assets::enqueue_scripts()
	 * @used-by Frontend_Assets::enqueue_styles()
	 */
	public function get_plugin_url(): string {
		return defined( 'BH_WP_BITCOIN_GATEWAY_URL' )
			? BH_WP_BITCOIN_GATEWAY_URL
			: plugins_url( $this->get_plugin_basename() );
	}

	/**
	 * Get the master public key (xpub...) for the specified gateway instance.
	 *
	 * @param string $gateway_id The id of the Bitcoin gateway.
	 *
	 * @return string
	 */
	public function get_master_public_key( string $gateway_id = 'bitcoin_gateway' ): string {
		$saved_settings = get_option( 'woocommerce_' . $gateway_id . '_settings', array() );
		return $saved_settings['xpub'] ?? '';
	}

	/**
	 * Get the absolute path to the plugin root on the server filesystem, with trailingslash.
	 */
	public function get_plugin_dir(): string {
		return defined( 'BH_WP_BITCOIN_GATEWAY_PATH' )
				? BH_WP_BITCOIN_GATEWAY_PATH
				: WP_PLUGIN_DIR . '/' . plugin_dir_path( $this->get_plugin_basename() );
	}
}
