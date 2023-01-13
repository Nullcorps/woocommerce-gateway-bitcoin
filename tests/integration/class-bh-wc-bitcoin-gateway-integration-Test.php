<?php
/**
 * Class Plugin_Test. Tests the root plugin setup.
 *
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway;

use BrianHenryIE\WC_Bitcoin_Gateway\API\API;

/**
 * Verifies the plugin has been instantiated and added to PHP's $GLOBALS variable.
 */
class Plugin_Integration_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Test the main plugin object is added to PHP's GLOBALS and that it is the correct class.
	 */
	public function test_plugin_instantiated(): void {

		$this->assertArrayHasKey( 'bh_wc_bitcoin_gateway', $GLOBALS );

		$this->assertInstanceOf( API::class, $GLOBALS['bh_wc_bitcoin_gateway'] );
	}

}
