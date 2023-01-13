<?php
/**
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway;

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
	 * Version compare during upgrade.
	 *
	 * @return string
	 */
	public function get_plugin_version(): string;

	public function get_plugin_url(): string;
}
