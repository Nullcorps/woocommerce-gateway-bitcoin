<?php

namespace Nullcorps\WC_Gateway_Bitcoin\WooCommerce;

use Codeception\Stub\Expected;
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

		$this->assertNotFalse( $scheduled_after );

	}

	/**
	 * @covers ::process_admin_options
	 */
	public function test_generates_new_addresses_when_xpub_changes(): void {

		$GLOBALS['nullcorps_wc_gateway_bitcoin'] = $this->makeEmpty(
			API_Interface::class,
			array()
		);

		$sut                   = new WC_Gateway_Bitcoin();
		$sut->settings['xpub'] = 'before';

		$_POST['woocommerce_bitcoin_gateway_xpub'] = 'after';

		assert( false === as_next_scheduled_action( Background_Jobs::GENERATE_NEW_ADDRESSES_HOOK ) );

		$sut->process_admin_options();

		$this->assertNotFalse( as_next_scheduled_action( Background_Jobs::GENERATE_NEW_ADDRESSES_HOOK ) );
	}


	/**
	 * @covers ::process_admin_options
	 */
	public function test_does_not_generate_new_addresses_when_xpub_does_not_change(): void {

		$GLOBALS['nullcorps_wc_gateway_bitcoin'] = $this->makeEmpty(
			API_Interface::class,
			array()
		);

		$sut                   = new WC_Gateway_Bitcoin();
		$sut->settings['xpub'] = 'same';

		$_POST['woocommerce_bitcoin_gateway_xpub'] = 'same';

		assert( false === as_next_scheduled_action( Background_Jobs::GENERATE_NEW_ADDRESSES_HOOK ) );

		$sut->process_admin_options();

		$this->assertFalse( as_next_scheduled_action( Background_Jobs::GENERATE_NEW_ADDRESSES_HOOK ) );
	}

}
