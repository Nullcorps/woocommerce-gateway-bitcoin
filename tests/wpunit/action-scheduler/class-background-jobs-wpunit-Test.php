<?php

namespace BrianHenryIE\WP_Bitcoin_Gateway\Action_Scheduler;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address_Repository;
use BrianHenryIE\WP_Bitcoin_Gateway\Integrations\WooCommerce\Model\WC_Bitcoin_Order;
use Codeception\Stub\Expected;
use BrianHenryIE\WP_Bitcoin_Gateway\API_Interface;
use lucatume\WPBrowser\TestCase\WPTestCase;
use WC_Order;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Bitcoin_Gateway\Action_Scheduler\Background_Jobs
 */
class Background_Jobs_WPUnit_Test extends WPTestCase {

	/**
	 * @covers ::schedule_generate_new_addresses
	 */
	public function test_schedule_generate_new_addresses(): void {

		$logger                     = new ColorLogger();
		$api                        = $this->makeEmpty( API_Background_Jobs_Interface::class );
		$bitcoin_address_repository = $this->makeEmpty( Bitcoin_Address_Repository::class );

		assert( false === as_has_scheduled_action( Background_Jobs_Actions_Interface::GENERATE_NEW_ADDRESSES_HOOK ) );

		/** @var Background_Jobs_Scheduling_Interface $sut */
		$sut = new Background_Jobs( $api, $bitcoin_address_repository, $logger );

		/**
		 * @see Background_Jobs::schedule_generate_new_addresses()
		 */
		$sut->schedule_generate_new_addresses();

		$this->assertTrue( as_has_scheduled_action( Background_Jobs_Actions_Interface::GENERATE_NEW_ADDRESSES_HOOK ) );
	}

	/**
	 * @covers ::schedule_check_newly_generated_bitcoin_addresses_for_transactions
	 */
	public function test_schedule_check_newly_generated_bitcoin_addresses_for_transactions(): void {

		$logger                     = new ColorLogger();
		$api                        = $this->makeEmpty( API_Background_Jobs_Interface::class );
		$bitcoin_address_repository = $this->makeEmpty( Bitcoin_Address_Repository::class );

		assert( false === as_has_scheduled_action( Background_Jobs_Actions_Interface::CHECK_NEW_ADDRESSES_TRANSACTIONS_HOOK ) );

		/** @var Background_Jobs_Scheduling_Interface $sut */
		$sut = new Background_Jobs( $api, $bitcoin_address_repository, $logger );

		/**
		 * @see Background_Jobs::schedule_check_newly_generated_bitcoin_addresses_for_transactions()
		 */
		$sut->schedule_check_newly_generated_bitcoin_addresses_for_transactions();

		$this->assertTrue( as_has_scheduled_action( Background_Jobs_Actions_Interface::CHECK_NEW_ADDRESSES_TRANSACTIONS_HOOK ) );
	}

	/**
	 * @covers ::schedule_check_newly_generated_bitcoin_addresses_for_transactions
	 */
	public function test_schedule_check_newly_generated_bitcoin_addresses_for_transactions_already_scheduled(): void {

		$logger                     = new ColorLogger();
		$api                        = $this->makeEmpty( API_Background_Jobs_Interface::class );
		$bitcoin_address_repository = $this->makeEmpty( Bitcoin_Address_Repository::class );

		as_schedule_single_action(
			timestamp: time(),
			hook: Background_Jobs_Actions_Interface::CHECK_NEW_ADDRESSES_TRANSACTIONS_HOOK,
		);

		assert( true === as_has_scheduled_action( Background_Jobs_Actions_Interface::CHECK_NEW_ADDRESSES_TRANSACTIONS_HOOK ) );

		/** @var Background_Jobs_Scheduling_Interface $sut */
		$sut = new Background_Jobs( $api, $bitcoin_address_repository, $logger );

		/**
		 * @see Background_Jobs::schedule_check_newly_generated_bitcoin_addresses_for_transactions()
		 */
		$sut->schedule_check_newly_generated_bitcoin_addresses_for_transactions();

		$this->assertTrue( as_has_scheduled_action( Background_Jobs_Actions_Interface::CHECK_NEW_ADDRESSES_TRANSACTIONS_HOOK ) );
	}

	/**
	 * @covers ::schedule_check_newly_generated_bitcoin_addresses_for_transactions
	 */
	public function test_schedule_check_newly_generated_bitcoin_addresses_for_transactions_with_specific_datetime(): void {

		$logger                     = new ColorLogger();
		$api                        = $this->makeEmpty( API_Background_Jobs_Interface::class );
		$bitcoin_address_repository = $this->makeEmpty( Bitcoin_Address_Repository::class );

		assert( false === as_has_scheduled_action( Background_Jobs_Actions_Interface::CHECK_NEW_ADDRESSES_TRANSACTIONS_HOOK ) );

		/** @var Background_Jobs_Scheduling_Interface $sut */
		$sut = new Background_Jobs( $api, $bitcoin_address_repository, $logger );

		$datetime = ( new \DateTimeImmutable() )->add( new \DateInterval( 'P1D' ) );

		/**
		 * @see Background_Jobs::schedule_check_newly_generated_bitcoin_addresses_for_transactions()
		 */
		$sut->schedule_check_newly_generated_bitcoin_addresses_for_transactions( $datetime );

		$scheduled_actions = as_get_scheduled_actions( array( 'hook' => Background_Jobs_Actions_Interface::CHECK_NEW_ADDRESSES_TRANSACTIONS_HOOK ) );
		/** @var \ActionScheduler_Action $scheduled_action */
		$scheduled_action = array_pop( $scheduled_actions );

		$result = $scheduled_action->get_schedule()->get_date();

		$this->assertEquals( $datetime->getTimestamp(), $result->getTimestamp() );
	}

	/**
	 * @covers ::schedule_check_newly_assigned_bitcoin_address_for_transactions
	 * @covers ::schedule_check_assigned_addresses_for_transactions
	 */
	public function test_schedule_check_newly_assigned_bitcoin_address_for_transactions(): void {

		$logger                     = new ColorLogger();
		$api                        = $this->makeEmpty( API_Background_Jobs_Interface::class );
		$bitcoin_address_repository = $this->makeEmpty( Bitcoin_Address_Repository::class );

		/** @var Background_Jobs_Scheduling_Interface $sut */
		$sut = new Background_Jobs( $api, $bitcoin_address_repository, $logger );

		/** @see Background_Jobs::schedule_check_newly_assigned_bitcoin_address_for_transactions() */
		$sut->schedule_check_newly_assigned_bitcoin_address_for_transactions();
	}

	/**
	 */
	public function test_check_unpaid_order_bad_order_id(): void {

		$this->markTestIncomplete();

		$logger                     = new ColorLogger();
		$api                        = $this->makeEmpty( API_Background_Jobs_Interface::class );
		$bitcoin_address_repository = $this->makeEmpty( Bitcoin_Address_Repository::class );

		/** @var Background_Jobs_Scheduling_Interface $sut */
		$sut = new Background_Jobs( $api, $bitcoin_address_repository, $logger );

		$order_id = 99;

		/** @see Background_Jobs:: */
		$sut->check_unpaid_order( $order_id );

		$this->assertTrue( $logger->hasErrorThatContains( 'Invalid order id 99 passed to check_unpaid_order()' ) );
	}


	/**
	 */
	public function test_check_unpaid_order_does_not_reschedule_job_when_order_status_paid(): void {

		$this->markTestIncomplete();

		$logger             = new ColorLogger();
		$bitcoin_order_mock = self::makeEmpty( WC_Bitcoin_Order::class );
		$api                = $this->makeEmpty(
			API_Background_Jobs_Interface::class,
			array(
				'get_order_details' => Expected::once(
					function ( WC_Order $order ) use ( $bitcoin_order_mock ) {

						$order->payment_complete();
						$order->save();

						return $bitcoin_order_mock;
					}
				),
			)
		);

		assert( false === as_has_scheduled_action( Background_Jobs_Actions_Interface::CHECK_ASSIGNED_ADDRESSES_TRANSACTIONS_HOOK ) );
		$bitcoin_address_repository = $this->makeEmpty(
			Bitcoin_Address_Repository::class,
			// array(
			// 'has_assigned_bitcoin_addresses' => Expected::once( false ),
			// 'get_assigned_bitcoin_addresses' => Expected::once( [] ),
			// )
		);

		/** @var Background_Jobs_Scheduling_Interface $sut */
		$sut = new Background_Jobs( $api, $bitcoin_address_repository, $logger );

		$order    = new WC_Order();
		$order_id = $order->save();

		/** @see Background_Jobs:: */
		$sut->check_unpaid_order( $order_id );

		$this->assertFalse( as_has_scheduled_action( Background_Jobs_Actions_Interface::CHECK_ASSIGNED_ADDRESSES_TRANSACTIONS_HOOK ) );
	}

	/**
	 */
	public function test_check_unpaid_order_logs_exception(): void {

		$this->markTestIncomplete();

		$logger                     = new ColorLogger();
		$api                        = $this->makeEmpty(
			API_Background_Jobs_Interface::class,
			array(
				'get_order_details' => Expected::once(
					function ( $order ) {
						throw new \Exception( 'Exception during updating order.' );
					}
				),
			)
		);
		$bitcoin_address_repository = $this->makeEmpty(
			Bitcoin_Address_Repository::class,
			// array(
			// 'has_assigned_bitcoin_addresses' => Expected::once( false ),
			// 'get_assigned_bitcoin_addresses' => Expected::once( [] ),
			// )
		);

		/** @var Background_Jobs_Scheduling_Interface $sut */
		$sut = new Background_Jobs( $api, $bitcoin_address_repository, $logger );

		$order = new WC_Order();
		$order->set_status( 'on-hold' );
		$order_id = $order->save();

		$sut->check_unpaid_order( $order_id );

		$this->assertTrue( $logger->hasErrorRecords() );
	}
}
