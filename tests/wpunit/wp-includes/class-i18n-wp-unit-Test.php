<?php
/**
 * Tests for I18n. Tests load_plugin_textdomain.
 *
 * @package    brianhenryie/bh-wp-bitcoin-gateway
 * @author  BrianHenryIE <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\WP_Includes;

/**
 * Class I18n_Test
 *
 * @see I18n
 * @coversDefaultClass \BrianHenryIE\WP_Bitcoin_Gateway\WP_Includes\I18n
 */
class I18n_WP_Unit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Checks if the filter run by WordPress in the load_plugin_textdomain() function is called.
	 *
	 * @see load_plugin_textdomain()
	 */
	public function test_load_plugin_textdomain_function(): void {

		$this->markTestSkipped( 'plugin_locale filter removed between 6.6.3 6.7.0.' );

		$called        = false;
		$actual_domain = null;

		$filter = function ( $locale, $domain ) use ( &$called, &$actual_domain ) {

			$called        = true;
			$actual_domain = $domain;

			return $locale;
		};

		add_filter( 'plugin_locale', $filter, 10, 2 );

		$i18n = new I18n();

		$i18n->load_plugin_textdomain();

		$this->assertTrue( $called, 'plugin_locale filter not called within load_plugin_textdomain() suggesting it has not been set by the plugin.' );
		$this->assertEquals( 'bh-wp-bitcoin-gateway', $actual_domain );
	}
}
