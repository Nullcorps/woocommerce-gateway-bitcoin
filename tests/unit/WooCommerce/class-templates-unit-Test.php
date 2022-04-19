<?php

namespace Nullcorps\WC_Gateway_Bitcoin\WooCommerce;

/**
 * @coversDefaultClass \Nullcorps\WC_Gateway_Bitcoin\WooCommerce\Templates
 */
class Templates_Unit_Test extends \Codeception\Test\Unit {

	protected function setUp(): void {
		parent::setUp();
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * @covers ::load_bitcoin_templates
	 */
	public function test_load_unrelated_template(): void {

		$sut = new Templates();

		$template      = '/path/to/woocommerce/templates/product-searchform.php';
		$template_name = 'product-searchform.php';

		$args          = array();
		$template_path = '';
		$default_path  = '';

		$result = $sut->load_bitcoin_templates( $template, $template_name, $args, $template_path, $default_path );

		$this->assertEquals( $template, $result );

	}


	/**
	 * @covers ::load_bitcoin_templates
	 */
	public function test_load_already_provided_template(): void {

		$sut = new Templates();

		// Use any existing file here for the test.
		$template      = __FILE__;
		$template_name = 'bitcoin-unpaid.php';
		$args          = array();
		$template_path = '';
		$default_path  = '';

		$result = $sut->load_bitcoin_templates( $template, $template_name, $args, $template_path, $default_path );

		$this->assertEquals( $template, $result );

	}


	/**
	 * @covers ::load_bitcoin_templates
	 */
	public function test_exists_in_theme_template(): void {

		$sut = new Templates();

		// Use any existing file here for the test.
		$template      = 'bitcoin-unpaid.php';
		$template_name = 'bitcoin-unpaid.php';
		$args          = array();
		$template_path = '';
		$default_path  = '';

		\WP_Mock::userFunction(
			'locate_template',
			array(
				'times'  => 1,
				'return' => 'expected-template-file-path',
			)
		);

		$result = $sut->load_bitcoin_templates( $template, $template_name, $args, $template_path, $default_path );

		$this->assertEquals( 'expected-template-file-path', $result );

	}

	/**
	 * @covers ::load_bitcoin_templates
	 */
	public function test_return_plugin_default_template(): void {

		$this->markTestSkipped( 'Redefining a constant. Needs to run in own process' );

		$sut = new Templates();

		// Use any existing file here for the test.
		$template      = 'bitcoin-unpaid.php';
		$template_name = 'bitcoin-unpaid.php';
		$args          = array();
		$template_path = '';
		$default_path  = '';

		\WP_Mock::userFunction(
			'locate_template',
			array(
				'times'  => 1,
				'return' => '', // WooCommerce's function returns an empty string, which I guess is falsey.
			)
		);

		if ( ! defined( 'NULLCORPS_WOOCOMMERCE_GATEWAY_BITCOIN_PATH' ) ) {
			define( 'NULLCORPS_WOOCOMMERCE_GATEWAY_BITCOIN_PATH', '/path/to/plugin' );
		}

		$result = $sut->load_bitcoin_templates( $template, $template_name, $args, $template_path, $default_path );

		$this->assertEquals( '/path/to/plugin/templates/bitcoin-unpaid.php', $result );

	}

}
