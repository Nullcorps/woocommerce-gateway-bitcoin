<?php
/**
 * Tests for I18n. Tests load_plugin_textdomain.
 *
 * @package    nullcorps/woocommerce-gateway-bitcoin
 * @author  BrianHenryIE <BrianHenryIE@gmail.com>
 */

namespace Nullcorps\WC_Gateway_Bitcoin\Includes;

/**
 *
 * @see I18n
 */
class I18n_Integration_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * AFAICT, this will fail until a translation has been added.
	 *
	 * @see load_plugin_textdomain()
	 * @see https://gist.github.com/GaryJones/c8259da3a4501fd0648f19beddce0249
	 */
	public function test_load_plugin_textdomain(): void {

		$this->markTestSkipped( 'Needs one translation before test might pass.' );

		global $plugin_root_dir;

		$this->assertTrue( file_exists( $plugin_root_dir . '/Languages/' ), '/Languages/ folder does not exist.' );

		// Seems to fail because there are no translations to load.
		$this->assertTrue( is_textdomain_loaded( 'woocommerce-gateway-bitcoin' ), 'i18n text domain not loaded.' );

	}

}
