<?php
/**
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

namespace Nullcorps\WC_Gateway_Bitcoin\API;

interface Settings_Interface {

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
