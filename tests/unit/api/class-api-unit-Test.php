<?php

namespace BrianHenryIE\WP_Bitcoin_Gateway\API;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address_Factory;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Wallet_Factory;
use BrianHenryIE\WP_Bitcoin_Gateway\Settings_Interface;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Bitcoin_Gateway\API\API
 */
class API_Unit_Test extends \Codeception\Test\Unit {

	protected function setUp(): void {
		parent::setUp();
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * @covers ::is_server_has_dependencies
	 */
	public function test_is_server_has_dependencies(): void {

		if ( ! function_exists( '\Patchwork\redefine' ) ) {
			$this->markTestSkipped( 'Patchwork not loaded' );
		}

		$logger                  = new ColorLogger();
		$settings                = $this->makeEmpty( Settings_Interface::class );
		$bitcoin_wallet_factory  = $this->makeEmpty( Bitcoin_Wallet_Factory::class );
		$bitcoin_address_factory = $this->makeEmpty( Bitcoin_Address_Factory::class );

		$sut = new API( $settings, $logger, $bitcoin_wallet_factory, $bitcoin_address_factory );

		\Patchwork\redefine(
			'function_exists',
			function ( string $function ): bool {
				if ( 'gmp_init' === $function ) {
					return false;
				}
				return \Patchwork\relay( array( $function ) );
			}
		);
		$result = $sut->is_server_has_dependencies();

		$this->assertFalse( $result );
	}


	/**
	 * @covers ::is_server_has_dependencies
	 */
	public function test_is_server_is_missing_dependencies(): void {

		if ( ! function_exists( '\Patchwork\redefine' ) ) {
			$this->markTestSkipped( 'Patchwork not loaded' );
		}

		$logger                  = new ColorLogger();
		$settings                = $this->makeEmpty( Settings_Interface::class );
		$bitcoin_wallet_factory  = $this->makeEmpty( Bitcoin_Wallet_Factory::class );
		$bitcoin_address_factory = $this->makeEmpty( Bitcoin_Address_Factory::class );

		$sut = new API( $settings, $logger, $bitcoin_wallet_factory, $bitcoin_address_factory );

		\Patchwork\redefine(
			'function_exists',
			function ( string $function ): bool {
				if ( 'gmp_init' === $function ) {
					return true;
				}
				return \Patchwork\relay( array( $function ) );
			}
		);
		$result = $sut->is_server_has_dependencies();

		$this->assertTrue( $result );
	}
}
