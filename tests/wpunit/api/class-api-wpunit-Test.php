<?php

namespace BrianHenryIE\WP_Bitcoin_Gateway\API;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Bitcoin_Gateway\Brick\Money\Money;
use BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce\Order;
use Codeception\Stub\Expected;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address_Factory;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Wallet;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Wallet_Factory;
use BrianHenryIE\WP_Bitcoin_Gateway\Settings_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce\Bitcoin_Gateway;
use WC_Payment_Gateways;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Bitcoin_Gateway\API\API
 */
class API_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::generate_new_addresses_for_wallet
	 */
	public function test_generate_addresses_for_gateway(): void {

		$this->markTestIncomplete();

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
		$blockchain_api          = $this->makeEmpty( Blockchain_API_Interface::class );
		$generate_address_api    = $this->makeEmpty( Generate_Address_API_Interface::class );
		$exchange_rate_api       = $this->makeEmpty( Exchange_Rate_API_Interface::class );

		$api = new API( $settings, $logger, $bitcoin_wallet_factory, $bitcoin_address_factory, $blockchain_api, $generate_address_api, $exchange_rate_api );

		$wc_payment_gateways = WC_Payment_Gateways::instance();
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
			function ( bool $carry, \WC_Payment_Gateway $gateway ): bool {
				return $carry && ( $gateway instanceof Bitcoin_Gateway );
			},
			true
		);

		$this->assertTrue( $all_bitcoin_gateways );

		unset( $wc_payment_gateways->payment_gateways['bitcoin_1'] );
		unset( $wc_payment_gateways->payment_gateways['bitcoin_2'] );
	}

	/**
	 * @covers ::is_bitcoin_gateway
	 */
	public function test_is_bitcoin_gateway(): void {

		$logger                  = new ColorLogger();
		$settings                = $this->makeEmpty( Settings_Interface::class );
		$bitcoin_wallet_factory  = $this->makeEmpty( Bitcoin_Wallet_Factory::class );
		$bitcoin_address_factory = $this->makeEmpty( Bitcoin_Address_Factory::class );
		$blockchain_api          = $this->makeEmpty( Blockchain_API_Interface::class );
		$generate_address_api    = $this->makeEmpty( Generate_Address_API_Interface::class );
		$exchange_rate_api       = $this->makeEmpty( Exchange_Rate_API_Interface::class );

		$api = new API( $settings, $logger, $bitcoin_wallet_factory, $bitcoin_address_factory, $blockchain_api, $generate_address_api, $exchange_rate_api );

		$wc_payment_gateways = WC_Payment_Gateways::instance();
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
		$blockchain_api          = $this->makeEmpty( Blockchain_API_Interface::class );
		$generate_address_api    = $this->makeEmpty( Generate_Address_API_Interface::class );
		$exchange_rate_api       = $this->makeEmpty( Exchange_Rate_API_Interface::class );

		$api = new API( $settings, $logger, $bitcoin_wallet_factory, $bitcoin_address_factory, $blockchain_api, $generate_address_api, $exchange_rate_api );

		$wc_payment_gateways = WC_Payment_Gateways::instance();
		$bitcoin_1           = new Bitcoin_Gateway( $api );
		$bitcoin_1->id       = 'bitcoin_1';
		$wc_payment_gateways->payment_gateways['bitcoin_1'] = $bitcoin_1;

		$order = new \WC_Order();
		$order->set_payment_method( 'bitcoin_1' );
		$order_id = $order->save();

		$result = $api->is_order_has_bitcoin_gateway( $order_id );

		$this->assertTrue( $result );
	}

	/**
	 * @covers ::convert_fiat_to_btc
	 */
	public function test_convert_fiat_to_btc(): void {

		$logger                  = new ColorLogger();
		$settings                = $this->makeEmpty( Settings_Interface::class );
		$bitcoin_wallet_factory  = $this->makeEmpty( Bitcoin_Wallet_Factory::class );
		$bitcoin_address_factory = $this->makeEmpty( Bitcoin_Address_Factory::class );
		$blockchain_api          = $this->makeEmpty( Blockchain_API_Interface::class );
		$generate_address_api    = $this->makeEmpty( Generate_Address_API_Interface::class );
		$exchange_rate_api       = $this->makeEmpty( Exchange_Rate_API_Interface::class );

		$api = new API( $settings, $logger, $bitcoin_wallet_factory, $bitcoin_address_factory, $blockchain_api, $generate_address_api, $exchange_rate_api );

		$transient_name = 'bh_wp_bitcoin_gateway_exchange_rate_USD';
		add_filter(
			"pre_transient_{$transient_name}",
			function ( $retval, $transient ) {
				return 23567;
			},
			10,
			2
		);

		$result = $api->convert_fiat_to_btc( Money::of( '10.99', 'USD' ) );

		$this->assertEquals( '0.0004663', $result );
	}

	/**
	 * @covers ::get_fresh_addresses_for_gateway
	 */
	public function test_get_fresh_addresses_for_gateway(): void {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );

		$addresses_result = array(
			self::make( Bitcoin_Address::class ),
			self::make(
				Bitcoin_Address::class,
				array(
					'get_raw_address' => 'success',
				)
			),
		);

		$wallet = self::make(
			Bitcoin_Wallet::class,
			array(
				'get_fresh_addresses' => Expected::once( $addresses_result ),
			)
		);

		$bitcoin_wallet_factory  = $this->makeEmpty(
			Bitcoin_Wallet_Factory::class,
			array(
				'get_post_id_for_wallet' => Expected::once( 123 ),
				'get_by_post_id'         => Expected::once( $wallet ),
			)
		);
		$bitcoin_address_factory = $this->makeEmpty( Bitcoin_Address_Factory::class );
		$blockchain_api          = $this->makeEmpty( Blockchain_API_Interface::class );
		$generate_address_api    = $this->makeEmpty( Generate_Address_API_Interface::class );
		$exchange_rate_api       = $this->makeEmpty( Exchange_Rate_API_Interface::class );

		$api = new API( $settings, $logger, $bitcoin_wallet_factory, $bitcoin_address_factory, $blockchain_api, $generate_address_api, $exchange_rate_api );

		$bitcoin_gateway                   = new Bitcoin_Gateway( $api );
		$bitcoin_gateway->settings['xpub'] = 'xpub';

		$result = $api->get_fresh_addresses_for_gateway( $bitcoin_gateway );

		$address = array_pop( $result );

		self::assertEquals( 'success', $address->get_raw_address() );
	}

	/**
	 * @covers ::is_fresh_address_available_for_gateway
	 */
	public function test_is_fresh_address_available_for_gateway_true(): void {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );

		$addresses_result = array(
			self::make( Bitcoin_Address::class ),
			self::make(
				Bitcoin_Address::class,
				array(
					'get_raw_address' => 'success',
				)
			),
		);

		$wallet = self::make(
			Bitcoin_Wallet::class,
			array(
				'get_fresh_addresses' => Expected::once( $addresses_result ),
			)
		);

		$bitcoin_wallet_factory  = $this->makeEmpty(
			Bitcoin_Wallet_Factory::class,
			array(
				'get_post_id_for_wallet' => Expected::once( 123 ),
				'get_by_post_id'         => Expected::once( $wallet ),
			)
		);
		$bitcoin_address_factory = $this->makeEmpty( Bitcoin_Address_Factory::class );
		$blockchain_api          = $this->makeEmpty( Blockchain_API_Interface::class );
		$generate_address_api    = $this->makeEmpty( Generate_Address_API_Interface::class );
		$exchange_rate_api       = $this->makeEmpty( Exchange_Rate_API_Interface::class );

		$api = new API( $settings, $logger, $bitcoin_wallet_factory, $bitcoin_address_factory, $blockchain_api, $generate_address_api, $exchange_rate_api );

		$bitcoin_gateway                   = new Bitcoin_Gateway( $api );
		$bitcoin_gateway->settings['xpub'] = 'xpub';

		$result = $api->is_fresh_address_available_for_gateway( $bitcoin_gateway );

		self::assertTrue( $result );
	}

	/**
	 * @covers ::get_fresh_address_for_order
	 */
	public function test_get_fresh_address_for_order(): void {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );

		$addresses_result = array(
			self::make(
				Bitcoin_Address::class,
				array(
					'get_raw_address' => 'success',
					'set_status'      => Expected::once(
						function ( $status ) {
							assert( 'assigned' === $status );
						}
					),
				)
			),
			self::make( Bitcoin_Address::class ),
		);

		$wallet = self::make(
			Bitcoin_Wallet::class,
			array(
				'get_fresh_addresses' => Expected::once( $addresses_result ),
			)
		);

		$bitcoin_wallet_factory = $this->makeEmpty(
			Bitcoin_Wallet_Factory::class,
			array(
				'get_post_id_for_wallet' => Expected::once( 123 ),
				'get_by_post_id'         => Expected::once( $wallet ),
			)
		);

		$bitcoin_address_factory = $this->makeEmpty( Bitcoin_Address_Factory::class );
		$blockchain_api          = $this->makeEmpty( Blockchain_API_Interface::class );
		$generate_address_api    = $this->makeEmpty( Generate_Address_API_Interface::class );
		$exchange_rate_api       = $this->makeEmpty( Exchange_Rate_API_Interface::class );

		$api = new API( $settings, $logger, $bitcoin_wallet_factory, $bitcoin_address_factory, $blockchain_api, $generate_address_api, $exchange_rate_api );

		$wc_payment_gateways                              = WC_Payment_Gateways::instance();
		$bitcoin_gateway                                  = new Bitcoin_Gateway( $api );
		$bitcoin_gateway->id                              = 'bitcoin';
		$bitcoin_gateway->settings['xpub']                = 'bitcoinxpub';
		$wc_payment_gateways->payment_gateways['bitcoin'] = $bitcoin_gateway;

		$order = new \WC_Order();
		$order->set_payment_method( 'bitcoin' );
		$order->save();

		$result = $api->get_fresh_address_for_order( $order );

		$this->assertEquals( 'success', $result->get_raw_address() );
	}

	/**
	 * @covers ::get_order_details
	 * @covers ::refresh_order
	 * @covers ::update_address_transactions
	 */
	public function test_get_order_details_no_transactions(): void {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );

		$address = self::make(
			Bitcoin_Address::class,
			array(
				'get_raw_address'             => Expected::exactly( 2, 'xpub' ),
				// First time checking an address, this is null.
				'get_blockchain_transactions' => Expected::exactly( 2, null ),
				'set_transactions'            => Expected::once(
					function ( array $refreshed_transactions ): void {
					}
				),
			)
		);

		$bitcoin_wallet_factory = $this->makeEmpty( Bitcoin_Wallet_Factory::class );

		$bitcoin_address_factory = $this->makeEmpty(
			Bitcoin_Address_Factory::class,
			array(
				'get_post_id_for_address' => Expected::once( 456 ),
				'get_by_post_id'          => Expected::once(
					function ( int $post_id ) use ( $address ): Bitcoin_Address {
						assert( 456 === $post_id );
						return $address;
					}
				),
			)
		);

		$blockchain_api       = self::makeEmpty(
			Blockchain_API_Interface::class,
			array(
				'get_blockchain_height' => Expected::once(
					function (): int {
						return 1000; }
				),
				'get_transactions'      => array(),
			)
		);
		$generate_address_api = $this->makeEmpty( Generate_Address_API_Interface::class );
		$exchange_rate_api    = $this->makeEmpty( Exchange_Rate_API_Interface::class );

		$api = new API( $settings, $logger, $bitcoin_wallet_factory, $bitcoin_address_factory, $blockchain_api, $generate_address_api, $exchange_rate_api );

		$order = new \WC_Order();
		$order->add_meta_data( Order::BITCOIN_ADDRESS_META_KEY, 'xpub', true );
		$order->save();

		$result = $api->get_order_details( $order, true );

		self::assertEmpty( $result->get_address()->get_blockchain_transactions() );
	}
}
