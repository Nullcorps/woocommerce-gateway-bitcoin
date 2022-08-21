<?php

namespace Nullcorps\WC_Gateway_Bitcoin\WP_Includes;

use BrianHenryIE\ColorLogger\ColorLogger;
use Codeception\Stub\Expected;
use Nullcorps\WC_Gateway_Bitcoin\API\Address_Storage\Crypto_Address;
use Nullcorps\WC_Gateway_Bitcoin\API\Address_Storage\Crypto_Address_Factory;
use Nullcorps\WC_Gateway_Bitcoin\API\Address_Storage\Crypto_Wallet;
use Nullcorps\WC_Gateway_Bitcoin\API_Interface;
use Nullcorps\WC_Gateway_Bitcoin\Settings_Interface;

/**
 * @coversDefaultClass \Nullcorps\WC_Gateway_Bitcoin\WP_Includes\CLI
 */
class CLI_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::update_address
	 */
	public function test_update_address_post_id(): void {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );
		$api      = $this->makeEmpty(
			API_Interface::class,
			array( 'update_address' => Expected::once( array() ) )
		);

		$factory = new Crypto_Address_Factory();
		$wallet  = $this->makeEmpty( Crypto_Wallet::class );

		$post_id = $factory->save_new( 'mockaddress', 0, $wallet );

		$sut = new CLI( $api, $settings, $logger );

		$sut->update_address( array( $post_id ) );

	}
}
