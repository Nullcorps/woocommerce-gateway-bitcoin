<?php
/**
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

namespace Nullcorps\WC_Gateway_Bitcoin\API;

interface Settings_Interface {

	/**
	 * The wp-content/uploads subdirectory to store files in.
	 *
	 * Previously was the global `$woobtc_filespath`.
	 *
	 * @return string
	 */
	public function get_uploads_subdirectory_name(): string;

	/**
	 * Needed to add links on plugins.php.
	 *
	 * @return string
	 */
	public function get_plugin_basename(): string;

	public function get_api_preference(): string;

	/**
	 * Semver plugin version for caching of js + css.
	 *
	 * @return string
	 */
	public function get_plugin_version(): string;
}
