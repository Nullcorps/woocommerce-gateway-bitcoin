<?php

namespace BrianHenryIE\WP_Bitcoin_Gateway\Action_Scheduler;

use BrianHenryIE\ColorLogger\ColorLogger;
use Codeception\Stub\Expected;
use BrianHenryIE\WP_Bitcoin_Gateway\API_Interface;
use WC_Order;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Bitcoin_Gateway\Action_Scheduler\Background_Jobs
 */
class Background_Jobs_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::check_unpaid_order
	 */
	public function test_check_unpaid_order(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty(
			API_Interface::class,
			array(
				'get_order_details' => Expected::once(
					function( $order ) {
						return array();
					}
				),
			)
		);

		$sut = new Background_Jobs( $api, $logger );

		$order    = new WC_Order();
		$order_id = $order->save();

		$sut->check_unpaid_order( $order_id );
	}

	/**
	 * @covers ::check_unpaid_order
	 */
	public function test_check_unpaid_order_bad_order_id(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty( API_Interface::class );

		$sut = new Background_Jobs( $api, $logger );

		$order_id = 99;

		$sut->check_unpaid_order( $order_id );

		$this->assertTrue( $logger->hasErrorThatContains( 'Invalid order id 99 passed to check_unpaid_order()' ) );
	}


	/**
	 * @covers ::check_unpaid_order
	 */
	public function test_check_unpaid_order_does_not_reschedule_job_when_order_status_paid(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty(
			API_Interface::class,
			array(
				'get_order_details' => Expected::once(
					function( WC_Order $order ) {

						$order->payment_complete();
						$order->save();

						return array();
					}
				),
			)
		);

		assert( false === as_has_scheduled_action( Background_Jobs::CHECK_UNPAID_ORDER_HOOK ) );

		$sut = new Background_Jobs( $api, $logger );

		$order    = new WC_Order();
		$order_id = $order->save();

		$sut->check_unpaid_order( $order_id );

		$this->assertFalse( as_has_scheduled_action( Background_Jobs::CHECK_UNPAID_ORDER_HOOK ) );

	}

	/**
	 * @covers ::check_unpaid_order
	 */
	public function test_check_unpaid_order_logs_exception(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty(
			API_Interface::class,
			array(
				'get_order_details' => Expected::once(
					function( $order ) {
						throw new \Exception( 'Exception during updating order.' );
					}
				),
			)
		);

		$sut = new Background_Jobs( $api, $logger );

		$order = new WC_Order();
		$order->set_status( 'on-hold' );
		$order_id = $order->save();

		$sut->check_unpaid_order( $order_id );

		$this->assertTrue( $logger->hasErrorRecords() );
	}
}
