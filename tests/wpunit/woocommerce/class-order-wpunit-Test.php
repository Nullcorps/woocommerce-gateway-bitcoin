<?php

namespace BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Bitcoin_Gateway\Action_Scheduler\Background_Jobs;
use BrianHenryIE\WP_Bitcoin_Gateway\API_Interface;
use Codeception\Stub\Expected;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce\Order
 */
class Order_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::schedule_check_for_transactions
	 */
	public function test_schedule_check_for_transactions(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_order_has_bitcoin_gateway' => Expected::once( true ),
			)
		);

		$sut = new Order( $api, $logger );

		$order    = new \WC_Order();
		$order_id = $order->save();

		assert( false === as_has_scheduled_action( Background_Jobs::CHECK_UNPAID_ORDER_HOOK ) );

		$sut->schedule_check_for_transactions( $order_id, 'pending', 'on-hold' );

		$this->assertTrue( as_has_scheduled_action( Background_Jobs::CHECK_UNPAID_ORDER_HOOK ) );
	}

	/**
	 * @covers ::schedule_check_for_transactions
	 */
	public function test_schedule_check_for_transactions_not_when_setting_to_other_status(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_order_has_bitcoin_gateway' => Expected::never(),
			)
		);

		$sut = new Order( $api, $logger );

		$order    = new \WC_Order();
		$order_id = $order->save();

		assert( false === as_has_scheduled_action( Background_Jobs::CHECK_UNPAID_ORDER_HOOK ) );

		$sut->schedule_check_for_transactions( $order_id, 'pending', 'processing' );

		$this->assertFalse( as_has_scheduled_action( Background_Jobs::CHECK_UNPAID_ORDER_HOOK ) );
	}


	/**
	 * @covers ::schedule_check_for_transactions
	 */
	public function test_schedule_check_for_transactions_not_when_not_bitcoin_gateway(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_order_has_bitcoin_gateway' => Expected::once( false ),
			)
		);

		$sut = new Order( $api, $logger );

		$order    = new \WC_Order();
		$order_id = $order->save();

		assert( false === as_has_scheduled_action( Background_Jobs::CHECK_UNPAID_ORDER_HOOK ) );

		$sut->schedule_check_for_transactions( $order_id, 'pending', 'on-hold' );

		$this->assertFalse( as_has_scheduled_action( Background_Jobs::CHECK_UNPAID_ORDER_HOOK ) );
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

		$order    = new \WC_Order();
		$order_id = $order->save();

		$hook              = Background_Jobs::CHECK_UNPAID_ORDER_HOOK;
		$args              = array( 'order_id' => $order_id );
		$timestamp         = time() + ( 5 * MINUTE_IN_SECONDS );
		$recurring_seconds = ( 5 * MINUTE_IN_SECONDS );
		as_schedule_recurring_action( $timestamp, $recurring_seconds, $hook, $args );

		assert( true === as_has_scheduled_action( Background_Jobs::CHECK_UNPAID_ORDER_HOOK ) );

		$sut->unschedule_check_for_transactions( $order_id, 'on-hold', 'processing' );

		$this->assertFalse( as_has_scheduled_action( Background_Jobs::CHECK_UNPAID_ORDER_HOOK ) );

	}

}
