<?php

namespace BrianHenryIE\WC_Bitcoin_Gateway\API;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Bitcoin_Gateway\API\Address_Storage\Crypto_Address;
use BrianHenryIE\WC_Bitcoin_Gateway\API\Address_Storage\Crypto_Address_Factory;
use BrianHenryIE\WC_Bitcoin_Gateway\API\Address_Storage\Crypto_Wallet_Factory;
use BrianHenryIE\WC_Bitcoin_Gateway\Settings_Interface;

/**
 * @coversNothing
 */
class API_Integration_Test extends \Codeception\TestCase\WPTestCase {

	public function test_update_address(): void {

		$this->markTestIncomplete();

		$test_wallet_address = 'bc1qkj5texg9utllnqknt9uggfa2jlgmlrs7hzrmu9';

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );

		$crypto_wallet_factory  = $this->makeEmpty( Crypto_Wallet_Factory::class );
		$crypto_address_factory = $this->makeEmpty( Crypto_Address_Factory::class );

		$sut = new API( $settings, $logger, $crypto_wallet_factory, $crypto_address_factory );

		$factory        = new Crypto_Address_Factory();
		$post_id        = $factory->save_new( $test_wallet_address, $address_index, $wallet );
		$crypto_address = new Crypto_Address( $post_id );

		$result = $sut->query_api_for_address_transactions( $crypto_address );

	}

}


