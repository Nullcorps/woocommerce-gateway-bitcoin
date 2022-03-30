<?php

namespace Nullcorps\WC_Gateway_Bitcoin\WooCommerce;

use Nullcorps\WC_Gateway_Bitcoin\Action_Scheduler\Background_Jobs;
use Nullcorps\WC_Gateway_Bitcoin\API\API_Interface;

/**
 * @coversDefaultClass \Nullcorps\WC_Gateway_Bitcoin\WooCommerce\WC_Gateway_Bitcoin
 */
class WC_Gateway_Bitcoin_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::process_payment
	 */
	public function test_process_payment_schedules_action(): void {

		$GLOBALS['nullcorps_wc_gateway_bitcoin'] = $this->makeEmpty(
			API_Interface::class,
			array(
				'get_fresh_address_for_order' => 'freshaddress',
				'get_exchange_rate'           => 44444.0,
				'convert_fiat_to_btc'         => 0.0001,
			)
		);

		$sut = new WC_Gateway_Bitcoin();

		$order = new \WC_Order();
		$order->set_total( '1000' );
		$order_id = $order->save();

		$scheduled_before = as_has_scheduled_action( Background_Jobs::CHECK_UNPAID_ORDER_HOOK );

		assert( false === $scheduled_before );

		$result = $sut->process_payment( $order_id );

		$scheduled_after = as_has_scheduled_action( Background_Jobs::CHECK_UNPAID_ORDER_HOOK );

		$this->assertTrue( $scheduled_after );

	}
}
