<?php
/**
 * Class Plugin_Test. Tests the root plugin setup.
 *
 * @package    brianhenryie/bh-wp-bitcoin-gateway
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway;

use BrianHenryIE\WP_Bitcoin_Gateway\API\API;

/**
 * Verifies the plugin has been instantiated and added to PHP's $GLOBALS variable.
 */
class Plugin_Integration_Test extends \lucatume\WPBrowser\TestCase\WPTestCase {

	/**
	 * Test the main plugin object is added to PHP's GLOBALS and that it is the correct class.
	 */
	public function test_plugin_instantiated(): void {

		$this->assertArrayHasKey( 'bh_wp_bitcoin_gateway', $GLOBALS );

		$this->assertInstanceOf( API::class, $GLOBALS['bh_wp_bitcoin_gateway'] );
	}
}
