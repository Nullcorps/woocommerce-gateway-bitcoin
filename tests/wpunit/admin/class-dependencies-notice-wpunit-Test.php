<?php

namespace BrianHenryIE\WP_Bitcoin_Gateway\Admin;

use BrianHenryIE\WP_Bitcoin_Gateway\API_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\Settings_Interface;
use Codeception\Stub\Expected;
use Codeception\TestCase\WPTestCase;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Bitcoin_Gateway\Admin\Dependencies_Notice
 */
class Dependencies_Notice_WPUnit_Test extends WPTestCase {

	/**
	 * @covers ::print_dependencies_notice
	 * @covers ::__construct
	 */
	public function test_dependencies_are_present(): void {

		$api = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_server_has_dependencies' => Expected::once( true ),
			)
		);

		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_basename' => Expected::never(),
			)
		);

		// Set to admin, as required to display the notice if the dependencies are not present.
		wp_set_current_user( 1 );

		$sut = new Dependencies_Notice( $api, $settings );

		ob_start();

		$sut->print_dependencies_notice();

		$result = ob_get_clean();

		$this->assertEmpty( $result );

	}


	/**
	 * @covers ::print_dependencies_notice
	 */
	public function test_dependencies_does_not_print_notice_for_non_admin(): void {

		$api = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_server_has_dependencies' => Expected::once( true ),
			)
		);

		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_basename' => Expected::never(),
			)
		);

		wp_set_current_user( 0 );

		$sut = new Dependencies_Notice( $api, $settings );

		ob_start();

		$sut->print_dependencies_notice();

		$result = ob_get_clean();

		$this->assertEmpty( $result );

	}

	/**
	 * @covers ::print_dependencies_notice
	 */
	public function test_dependencies_not_present(): void {

		$api = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_server_has_dependencies' => Expected::once( false ),
			)
		);

		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_basename' => Expected::exactly( 2, 'bh-wp-bitcoin-gateway/bh-wp-bitcoin-gateway.php' ),
			)
		);

		wp_set_current_user( 1 );

		$sut = new Dependencies_Notice( $api, $settings );

		ob_start();

		$sut->print_dependencies_notice();

		$result = ob_get_clean();

		$this->assertStringContainsString( 'https://www.php.net/manual/en/book.gmp.php', $result );

	}

}
