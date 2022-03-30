<?php

namespace Nullcorps\WC_Gateway_Bitcoin\Action_Scheduler;

use BrianHenryIE\ColorLogger\ColorLogger;
use Codeception\Stub\Expected;
use Nullcorps\WC_Gateway_Bitcoin\API\API_Interface;
use WC_Order;

/**
 * @coversDefaultClass \Nullcorps\WC_Gateway_Bitcoin\Action_Scheduler\Background_Jobs
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
						return array(); }
				),
			)
		);

		$sut = new Background_Jobs( $api, $logger );

		$order    = new WC_Order();
		$order_id = $order->save();

		assert( false === as_has_scheduled_action( Background_Jobs::CHECK_UNPAID_ORDER_HOOK ) );

		$sut->check_unpaid_order( $order_id );

		$this->assertTrue( as_has_scheduled_action( Background_Jobs::CHECK_UNPAID_ORDER_HOOK ) );
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

		$this->assertTrue( $logger->hasErrorThatContains( 'Invalid order id passed to check_unpaid_order()' ) );
	}
}
