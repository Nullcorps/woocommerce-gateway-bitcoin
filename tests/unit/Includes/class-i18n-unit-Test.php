<?php
/**
 *
 *
 * @package    nullcorps/woocommerce-gateway-bitcoin
 * @author  BrianHenryIE <BrianHenryIE@gmail.com>
 */

namespace Nullcorps\WC_Gateway_Bitcoin\Includes;

/**
 * Class Plugin_WP_Mock_Test
 *
 * @coversDefaultClass \Nullcorps\WC_Gateway_Bitcoin\Includes\I18n
 */
class I18n_Unit_Test extends \Codeception\Test\Unit {

	protected function setUp(): void {
		parent::setUp();
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * Verify load_plugin_textdomain is correctly called.
	 *
	 * @covers ::load_plugin_textdomain
	 */
	public function test_load_plugin_textdomain(): void {

		$this->markTestSkipped( 'Symlinks are messing with the correct value.' );

		global $plugin_root_dir;

		\WP_Mock::userFunction(
			'plugin_basename',
			array(
				'args'   => array(
					\WP_Mock\Functions::type( 'string' ),
				),
				'return' => 'nullcorps-wc-gateway-bitcoin/nullcorps-wc-gateway-bitcoin.php',
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'load_plugin_textdomain',
			array(
				'times' => 1,
				'args'  => array(
					'nullcorps-wc-gateway-bitcoin',
					false,
					'nullcorps-wc-gateway-bitcoin/languages/',
				),
			)
		);

		$i18n = new I18n();
		$i18n->load_plugin_textdomain();
	}
}
