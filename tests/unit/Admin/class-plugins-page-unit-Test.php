<?php

namespace Nullcorps\WC_Gateway_Bitcoin\Admin;

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

		$sut = new Plugins_Page();

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

		$sut = new Plugins_Page();

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

}
