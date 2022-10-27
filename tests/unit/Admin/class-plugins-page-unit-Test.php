<?php

namespace Nullcorps\WC_Gateway_Bitcoin\Admin;

use Codeception\Stub\Expected;
use Nullcorps\WC_Gateway_Bitcoin\Settings_Interface;

/**
 * @coversDefaultClass \Nullcorps\WC_Gateway_Bitcoin\Admin\Plugins_Page
 */
class Plugins_Page_Unit_Test extends \Codeception\Test\Unit {

	protected function setUp(): void {
		parent::setUp();
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * @covers ::add_settings_action_link
	 */
	public function test_add_settings_action_link(): void {

		$settings = $this->makeEmpty( Settings_Interface::class );
		$sut      = new Plugins_Page( $settings );

		\WP_Mock::userFunction(
			'is_plugin_active',
			array(
				'times'  => 1,
				'args'   => array( 'woocommerce/woocommerce.php' ),
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'admin_url',
			array(
				'times'      => 1,
				'return_arg' => true,
			)
		);

		\WP_Mock::userFunction(
			'__',
			array(
				'times'      => 1,
				'return_arg' => true,
			)
		);

		$result = $sut->add_settings_action_link( array() );

		$this->assertCount( 1, $result );

		$this->assertStringContainsString( 'Settings', $result[0] );
	}


	/**
	 * @covers ::add_settings_action_link
	 */
	public function test_add_settings_action_link_woocommerce_inactive(): void {

		$settings = $this->makeEmpty( Settings_Interface::class );
		$sut      = new Plugins_Page( $settings );

		\WP_Mock::userFunction(
			'is_plugin_active',
			array(
				'times'  => 1,
				'args'   => array( 'woocommerce/woocommerce.php' ),
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'admin_url',
			array(
				'times'      => 0,
				'return_arg' => true,
			)
		);

		\WP_Mock::userFunction(
			'__',
			array(
				'times'      => 0,
				'return_arg' => true,
			)
		);

		$result = $sut->add_settings_action_link( array() );
	}

	/**
	 * @covers ::split_author_link_into_two_links
	 */
	public function test_split_author_link_into_two_links(): void {

		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_basename' => Expected::once( 'woocommerce-gateway-bitcoin/woocommerce-gateway-bitcoin.php' ),
			)
		);
		$sut      = new Plugins_Page( $settings );

		$plugin_meta     = array(
			0 => 'Version 1.3.3',
			1 => 'By <a href="https://github.com/Nullcorps/">Nullcorps, BrianHenryIE</a>',
			2 => '<a href="http://github.com/BrianHenryIE/woocommerce-gateway-bitcoin/" aria-label="Visit plugin site for WooCommerce Gateway Bitcoin">Visit plugin site</a>',
		);
		$plugin_filename = 'woocommerce-gateway-bitcoin/woocommerce-gateway-bitcoin.php';

		$result = $sut->split_author_link_into_two_links( $plugin_meta, $plugin_filename );

		$updated = 'By <a href="https://github.com/Nullcorps/">Nullcorps</a>, <a href="https://brianhenry.ie/">BrianHenryIE</a>';

		$this->assertEquals( $updated, $result[1] );
	}
}
