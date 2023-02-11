<?php
/**
 * Required settings for the plugin.
 *
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway;

/**
 * Typed settings, presumably saved in `wp_options`.
 */
interface Settings_Interface {

	/**
	 * Needed to add links on plugins.php.
	 */
	public function get_plugin_basename(): string;

	/**
	 * Semver plugin version for caching of js + css.
	 * Version compare during upgrade.
	 */
	public function get_plugin_version(): string;

	/**
	 * The URL to the plugin folder.
	 * E.g. `https://example.org/wp-content/plugins/bh-wc-bitcoin-gateway/`.
	 *
	 * Has trailing slash.
	 */
	public function get_plugin_url(): string;

	/**
	 * Get the xpub/ypub/zpub of the gateway.
	 *
	 * @param string $gateway_id Optionally specify the gateway id if there are multiple instances.
	 */
	public function get_master_public_key( string $gateway_id = 'bitcoin_gateway' ): string;

	/**
	 * Get the absolute path to the plugin root on the server filesystem, with trailingslash.
	 */
	public function get_plugin_dir(): string;
}
