<?php
/**
 * Tests for the root plugin file.
 *
 * @package    nullcorps/woocommerce-gateway-bitcoin
 * @author  BrianHenryIE <BrianHenryIE@gmail.com>
 */

namespace Nullcorps\WC_Gateway_Bitcoin;

use Nullcorps\WC_Gateway_Bitcoin\BrianHenryIE\WP_Logger\Logger;

/**
 * Class Plugin_WP_Mock_Test
 */
class Plugin_Unit_Test extends \Codeception\Test\Unit {

	protected function setUp() : void {
		parent::setUp();
		\WP_Mock::setUp();
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
		parent::tearDown();
	}

	/**
	 * Verifies the plugin initialization.
	 * Verifies the plugin does not output anything to screen.
	 */
	public function test_plugin_include(): void {

		// Prevents code-coverage counting, and removes the need to define the WordPress functions that are used in that class.
		\Patchwork\redefine(
			array( Nullcorps_WC_Gateway_Bitcoin::class, '__construct' ),
			function() {}
		);
		\Patchwork\redefine(
			array( Logger::class, '__construct' ),
			function() {}
		);

		global $plugin_root_dir;

		\WP_Mock::userFunction(
			'plugin_dir_path',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => $plugin_root_dir . '/',
			)
		);

		// Defined in `bootstrap.php`.
		global $plugin_basename;
		\WP_Mock::userFunction(
			'plugin_basename',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => $plugin_basename,
			)
		);

		\WP_Mock::userFunction(
			'plugins_url',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => 'http://localhost:8080/nullcorps-woocommerce-gateway-bitcoin/',
			)
		);

		\WP_Mock::userFunction(
			'trailingslashit',
			array(
				'args'    => array( \WP_Mock\Functions::type( 'string' ) ),
				'ret_arg' => true,
			)
		);

		\WP_Mock::userFunction(
			'register_activation_hook'
		);

		\WP_Mock::userFunction(
			'register_deactivation_hook'
		);

		ob_start();

		include $plugin_root_dir . '/nullcorps-woocommerce-gateway-bitcoin.php';

		$printed_output = ob_get_contents();

		ob_end_clean();

		$this->assertEmpty( $printed_output );

		$this->assertArrayHasKey( 'nullcorps_wc_gateway_bitcoin', $GLOBALS );

		$this->assertInstanceOf( API_Interface::class, $GLOBALS['nullcorps_wc_gateway_bitcoin'] );

	}

}
