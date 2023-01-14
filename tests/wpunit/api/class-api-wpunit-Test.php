<?php

namespace BrianHenryIE\WC_Bitcoin_Gateway\API;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Bitcoin_Gateway\API_Interface;
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

	/**
	 * @covers ::get_bitcoin_gateways
	 */
	public function test_get_bitcoin_gateways(): void {

		$logger                  = new ColorLogger();
		$settings                = $this->makeEmpty( Settings_Interface::class );
		$bitcoin_wallet_factory  = $this->makeEmpty( Bitcoin_Wallet_Factory::class );
		$bitcoin_address_factory = $this->makeEmpty( Bitcoin_Address_Factory::class );

		$api = new API( $settings, $logger, $bitcoin_wallet_factory, $bitcoin_address_factory );

		$wc_payment_gateways = \WC_Payment_Gateways::instance();
		$bitcoin_1           = new Bitcoin_Gateway( $api );
		$bitcoin_1->id       = 'bitcoin_1';

		$wc_payment_gateways->payment_gateways['bitcoin_1'] = $bitcoin_1;

		$bitcoin_2     = new Bitcoin_Gateway( $api );
		$bitcoin_2->id = 'bitcoin_2';

		$wc_payment_gateways->payment_gateways['bitcoin_2'] = $bitcoin_2;

		$result = $api->get_bitcoin_gateways();

		$this->assertCount( 2, $result );

		$all_bitcoin_gateways = array_reduce(
			$result,
			function( bool $carry, \WC_Payment_Gateway $gateway ):bool {
				return $carry && ( $gateway instanceof Bitcoin_Gateway );
			},
			true
		);

		$this->assertTrue( $all_bitcoin_gateways );
	}

	/**
	 * @covers ::is_bitcoin_gateway
	 */
	public function test_is_bitcoin_gateway(): void {

		$logger                  = new ColorLogger();
		$settings                = $this->makeEmpty( Settings_Interface::class );
		$bitcoin_wallet_factory  = $this->makeEmpty( Bitcoin_Wallet_Factory::class );
		$bitcoin_address_factory = $this->makeEmpty( Bitcoin_Address_Factory::class );

		$api = new API( $settings, $logger, $bitcoin_wallet_factory, $bitcoin_address_factory );

		$wc_payment_gateways = \WC_Payment_Gateways::instance();
		$bitcoin_1           = new Bitcoin_Gateway( $api );
		$bitcoin_1->id       = 'bitcoin_1';
		$wc_payment_gateways->payment_gateways['bitcoin_1'] = $bitcoin_1;

		$result = $api->is_bitcoin_gateway( 'bitcoin_1' );

		$this->assertTrue( $result );
	}

	/**
	 * @covers ::is_order_has_bitcoin_gateway
	 */
	public function test_is_order_has_bitcoin_gateway(): void {

		$logger                  = new ColorLogger();
		$settings                = $this->makeEmpty( Settings_Interface::class );
		$bitcoin_wallet_factory  = $this->makeEmpty( Bitcoin_Wallet_Factory::class );
		$bitcoin_address_factory = $this->makeEmpty( Bitcoin_Address_Factory::class );

		$api = new API( $settings, $logger, $bitcoin_wallet_factory, $bitcoin_address_factory );

		$wc_payment_gateways = \WC_Payment_Gateways::instance();
		$bitcoin_1           = new Bitcoin_Gateway( $api );
		$bitcoin_1->id       = 'bitcoin_1';
		$wc_payment_gateways->payment_gateways['bitcoin_1'] = $bitcoin_1;

		$order = new \WC_Order();
		$order->set_payment_method( 'bitcoin_1' );
		$order_id = $order->save();

		$result = $api->is_order_has_bitcoin_gateway( $order_id );

		$this->assertTrue( $result );
	}
}
