<?php

namespace Nullcorps\WC_Gateway_Bitcoin\Frontend;

use BrianHenryIE\ColorLogger\ColorLogger;
use Codeception\Stub\Expected;
use Nullcorps\WC_Gateway_Bitcoin\API\API_Interface;
use Nullcorps\WC_Gateway_Bitcoin\API\Settings_Interface;

/**
 * @coversDefaultClass \Nullcorps\WC_Gateway_Bitcoin\Frontend\Frontend
 */
class Frontend_Unit_Test extends \Codeception\Test\Unit {

	protected function setUp(): void {
		parent::setUp();
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * Verifies enqueue_styles() calls wp_enqueue_style() with appropriate parameters.
	 * Verifies the .css file exists.
	 *
	 * @covers ::enqueue_styles
	 * @covers ::__construct
	 * @see wp_enqueue_style()
	 */
	public function test_enqueue_styles(): void {

		global $plugin_root_dir;
		$plugin_root_url = 'http://localhost:8080/woocommerce-gateway-bitcoin/wp-content/plugins/woocommerce-gateway-bitcoin';

		$logger   = new ColorLogger();
		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_version' => Expected::once(
					function() {
						return '1.0.0'; }
				),
			)
		);

		// Return any old url.
		\WP_Mock::userFunction(
			'plugin_dir_url',
			array(
				'return' => $plugin_root_url . '/Frontend/',
				'times'  => 1,
			)
		);

		$css_file = $plugin_root_dir . '/Frontend/css/woocommerce-gateway-bitcoin.css';
		$css_url  = $plugin_root_url . '/Frontend/css/woocommerce-gateway-bitcoin.css';

		\WP_Mock::userFunction(
			'wp_enqueue_style',
			array(
				'times' => 1,
				'args'  => array( 'nullcorps-wc-gateway-bitcoin', $css_url, array(), '1.0.0', 'all' ),
			)
		);

		$GLOBALS['order-received'] = 123;

		$sut = new Frontend( $api, $settings, $logger );

		$sut->enqueue_styles();

		$this->assertFileExists( $css_file );
	}

	/**
	 * @covers ::enqueue_styles
	 */
	public function test_enqueue_styles_not_on_other_pages(): void {

		global $plugin_root_dir;
		$plugin_root_url = 'http://localhost:8080/woocommerce-gateway-bitcoin/wp-content/plugins/woocommerce-gateway-bitcoin';

		$logger   = new ColorLogger();
		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_version' => Expected::never(),
			)
		);

		// Return any old url.
		\WP_Mock::userFunction(
			'plugin_dir_url',
			array(
				'times' => 0,
			)
		);

		$css_file = $plugin_root_dir . '/Frontend/css/woocommerce-gateway-bitcoin.css';
		$css_url  = $plugin_root_url . '/Frontend/css/woocommerce-gateway-bitcoin.css';

		\WP_Mock::userFunction(
			'wp_enqueue_style',
			array(
				'times' => 0,
			)
		);

		// Omitted for test.
		// $GLOBALS['order-received'] = 123;

		$sut = new Frontend( $api, $settings, $logger );

		$sut->enqueue_styles();

		$this->assertFileExists( $css_file );
	}

}
