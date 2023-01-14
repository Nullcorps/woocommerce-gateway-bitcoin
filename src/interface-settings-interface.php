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

	/**
	 * Semver plugin version for caching of js + css.
	 * Version compare during upgrade.
	 *
	 * @return string
	 */
	public function get_plugin_version(): string;

	/**
	 * The URL to the plugin folder.
	 * E.g. `https://example.org/wp-content/plugins/bh-wc-bitcoin-gateway`.
	 */
	public function get_plugin_url(): string;


	public function get_xpub( string $gateway_id = 'bitcoin_gateway' ): string;
}
