<?php

namespace BrianHenryIE\WC_Bitcoin_Gateway\API;

use BrianHenryIE\ColorLogger\ColorLogger;
use Codeception\Stub\Expected;
use BrianHenryIE\WC_Bitcoin_Gateway\API\Addresses\Bitcoin_Address;
use BrianHenryIE\WC_Bitcoin_Gateway\API\Addresses\Bitcoin_Address_Factory;
use BrianHenryIE\WC_Bitcoin_Gateway\API\Addresses\Bitcoin_Wallet;
use BrianHenryIE\WC_Bitcoin_Gateway\API\Addresses\Bitcoin_Wallet_Factory;
use BrianHenryIE\WC_Bitcoin_Gateway\Settings_Interface;
use BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\Bitcoin_Gateway;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Bitcoin_Gateway\API\API
 */
class API_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::generate_new_addresses_for_wallet
	 */
	public function test_generate_addresses_for_gateway(): void {

		$test_xpub = 'zpub6n37hVDJHFyDG1hBERbMBVjEd6ws6zVhg9bMs5STo21i9DgDE9Z9KTedtGxikpbkaucTzpj79n6Xg8Zwb9kY8bd9GyPh9WVRkM55uK7w97K';

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );

		$wallet                 = $this->makeEmpty( Bitcoin_Wallet::class );
		$bitcoin_wallet_factory = $this->makeEmpty(
			Bitcoin_Wallet_Factory::class,
			array(
				'get_post_id_for_wallet' => Expected::once( 123 ),
				'get_by_post_id'         => Expected::once( $wallet ),
			)
		);

		$address                 = $this->makeEmpty( Bitcoin_Address::class );
		$bitcoin_address_factory = $this->makeEmpty(
			Bitcoin_Address_Factory::class,
			array(
				'save_new'       => Expected::exactly( 5, 123 ),
				'get_by_post_id' => Expected::exactly( 5, $address ),
			)
		);

		$api = new API( $settings, $logger, $bitcoin_wallet_factory, $bitcoin_address_factory );

		$result = $api->generate_new_addresses_for_wallet( $test_xpub, 5 );

	}

}
