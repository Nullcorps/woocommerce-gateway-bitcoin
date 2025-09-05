<?php

namespace BrianHenryIE\WP_Bitcoin_Gateway\API;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address_Factory;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Wallet_Factory;
use BrianHenryIE\WP_Bitcoin_Gateway\Settings_Interface;
use lucatume\WPBrowser\TestCase\WPTestCase;

/**
 * @coversNothing
 */
class API_Integration_Test extends WPTestCase {

	public function test_update_address(): void {

		$this->markTestIncomplete();

		$test_wallet_address = 'bc1qkj5texg9utllnqknt9uggfa2jlgmlrs7hzrmu9';

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );

		$bitcoin_wallet_factory  = $this->makeEmpty( Bitcoin_Wallet_Factory::class );
		$bitcoin_address_factory = $this->makeEmpty( Bitcoin_Address_Factory::class );

		$sut = new API( $settings, $logger, $bitcoin_wallet_factory, $bitcoin_address_factory );

		$factory         = new Bitcoin_Address_Factory();
		$post_id         = $factory->save_new( $test_wallet_address, $address_index, $wallet );
		$bitcoin_address = new Bitcoin_Address( $post_id );

		$result = $sut->update_address_transactions( $bitcoin_address );
	}
}
