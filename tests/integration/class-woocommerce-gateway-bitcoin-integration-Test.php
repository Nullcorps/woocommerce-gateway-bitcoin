<?php
/**
 * Class Plugin_Test. Tests the root plugin setup.
 *
 * @package    nullcorps/woocommerce-gateway-bitcoin
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */

namespace Nullcorps\WC_Gateway_Bitcoin;

use Nullcorps\WC_Gateway_Bitcoin\API\API;

/**
 * Verifies the plugin has been instantiated and added to PHP's $GLOBALS variable.
 */
class Plugin_Integration_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Test the main plugin object is added to PHP's GLOBALS and that it is the correct class.
	 */
	public function test_plugin_instantiated(): void {

		$this->assertArrayHasKey( 'nullcorps_wc_gateway_bitcoin', $GLOBALS );

		$this->assertInstanceOf( API::class, $GLOBALS['nullcorps_wc_gateway_bitcoin'] );
	}

}
