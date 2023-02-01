<?php

namespace BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Bitcoin_Gateway\API_Interface;
use Codeception\Stub\Expected;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\Order
 */
class Order_Unit_Test extends \Codeception\Test\Unit {

	protected function setUp() : void {
		parent::setUp();
		\WP_Mock::setUp();
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
		parent::tearDown();
	}

	/**
	 * TODO: Find a better meta key.
	 */
	public function test_verify_const(): void {
		$this->assertEquals( 'bh_wc_bitcoin_gateway_address', Order::BITCOIN_ADDRESS_META_KEY );
	}


	/**
	 * @covers ::unschedule_check_for_transactions
	 */
	public function test_unschedule_check_for_transactions(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_order_has_bitcoin_gateway' => Expected::once( true ),
			)
		);

		$sut = new Order( $api, $logger );

		$order_id = '123';

		\WP_Mock::userFunction(
			'as_unschedule_action',
			array(
				'times' => 1,
			)
		);

		$sut->unschedule_check_for_transactions( $order_id, 'on-hold', 'processing' );

	}

	/**
	 * @covers ::unschedule_check_for_transactions
	 */
	public function test_unschedule_check_for_transactions_return_when_not_bitcoin_gateway(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_order_has_bitcoin_gateway' => Expected::once( false ),
			)
		);

		$sut = new Order( $api, $logger );

		$order_id = '123';

		\WP_Mock::userFunction(
			'as_unschedule_action',
			array(
				'times' => 0,
			)
		);

		$sut->unschedule_check_for_transactions( $order_id, 'on-hold', 'processing' );
	}

	/**
	 * @covers ::unschedule_check_for_transactions
	 */
	public function test_unschedule_check_for_transactions_return_when_not_paid(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_order_has_bitcoin_gateway' => Expected::never(),
			)
		);

		$sut = new Order( $api, $logger );

		$order_id = '123';

		\WP_Mock::userFunction(
			'as_unschedule_action',
			array(
				'times' => 0,
			)
		);

		$sut->unschedule_check_for_transactions( $order_id, 'on-hold', 'pending' );
	}
}
