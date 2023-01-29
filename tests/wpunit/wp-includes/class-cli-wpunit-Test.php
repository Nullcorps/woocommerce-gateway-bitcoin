<?php

namespace BrianHenryIE\WC_Bitcoin_Gateway\WP_Includes;

use BrianHenryIE\ColorLogger\ColorLogger;
use Codeception\Stub\Expected;
use BrianHenryIE\WC_Bitcoin_Gateway\API\Addresses\Bitcoin_Address;
use BrianHenryIE\WC_Bitcoin_Gateway\API\Addresses\Bitcoin_Address_Factory;
use BrianHenryIE\WC_Bitcoin_Gateway\API\Addresses\Bitcoin_Wallet;
use BrianHenryIE\WC_Bitcoin_Gateway\API_Interface;
use BrianHenryIE\WC_Bitcoin_Gateway\Settings_Interface;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Bitcoin_Gateway\WP_Includes\CLI
 */
class CLI_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::check_transactions
	 */
	public function test_update_address_post_id(): void {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );
		$api      = $this->makeEmpty(
			API_Interface::class,
			array( 'update_address' => Expected::once( array() ) )
		);

		$factory = new Bitcoin_Address_Factory();
		$wallet  = $this->makeEmpty( Bitcoin_Wallet::class );

		$post_id = (string) $factory->save_new( 'mockaddress', 0, $wallet );

		$sut = new CLI( $api, $settings, $logger );

		$sut->check_transactions( array( $post_id ), array() );

	}
}
