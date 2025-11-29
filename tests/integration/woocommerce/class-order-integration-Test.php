<?php

namespace BrianHenryIE\WP_Bitcoin_Gateway\Integrations\WooCommerce;

use BrianHenryIE\WP_Bitcoin_Gateway\Action_Scheduler\Background_Jobs;

/**
 * @coversNothing
 */
class Order_Integration_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * It seemed the check-unpaid-order job was not being cancelled when the order was paid, but the test
	 * passed without modifying anything.
	 */
	public function test_order_payment_cancels_scheduled_task(): void {

		$order = new \WC_Order();
		$order->set_payment_method( 'bitcoin_gateway' );
		$order_id = $order->save();

		$order->set_status( 'on-hold' );
		$order->save();

		$hook = Background_Jobs_Actions_Interface::CHECK_ASSIGNED_ADDRESSES_TRANSACTIONS_HOOK;
		$args = array( 'order_id' => $order_id );

		assert( as_has_scheduled_action( $hook, $args ) );

		$order->payment_complete();
		$order->save();

		$this->assertFalse( as_has_scheduled_action( $hook, $args ) );
	}
}
