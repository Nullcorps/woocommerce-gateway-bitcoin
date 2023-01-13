<?php

namespace BrianHenryIE\WC_Bitcoin_Gateway\API;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Bitcoin_Gateway\API\Settings
 */
class Settings_Unit_Test extends \Codeception\Test\Unit {

	/**
	 * @covers ::get_plugin_name
	 */
	public function test_get_plugin_name(): void {

		$sut = new Settings();

		$this->assertEquals( 'Bitcoin Gateway for WooCommerce', $sut->get_plugin_name() );
	}

	/**
	 * @covers ::get_plugin_version
	 */
	public function test_get_plugin_version(): void {
		global $plugin_root_dir, $plugin_name_php;

		$plugin_file = $plugin_root_dir . DIRECTORY_SEPARATOR . 'bh-wc-bitcoin-gateway.php';

		$plugin_file_contents = file_get_contents( $plugin_file );

		preg_match( '/\s+\*\s+Version:\s+(\d+\.\d+\.\d+)/', $plugin_file_contents, $output_array );

		$sut = new Settings();

		$this->assertEquals( $sut->get_plugin_version(), $output_array[1] );
	}
}
