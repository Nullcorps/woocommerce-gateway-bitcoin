<?php

namespace BrianHenryIE\WP_Bitcoin_Gateway\Action_Scheduler;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address_Repository;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Model\Addresses_Generation_Result;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Model\Check_Assigned_Addresses_For_Transactions_Result;
use Codeception\Stub\Expected;
use WP_Mock;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Bitcoin_Gateway\Action_Scheduler\Background_Jobs
 */
class Background_Jobs_Unit_Test extends \Codeception\Test\Unit {

	protected function setUp(): void {
		parent::setUp();
		WP_Mock::setUp();
	}

	public function tearDown(): void {
		WP_Mock::tearDown();
		parent::tearDown();
	}

	/**
	 * @covers ::generate_new_addresses
	 * @covers ::__construct
	 */
	public function test_generate_new_adresses_action(): void {

		$logger                     = new ColorLogger();
		$api                        = $this->makeEmpty(
			API_Background_Jobs_Interface::class,
			array(
				'generate_new_addresses' => Expected::once(
					function () {
						return array( $this->createMock( Addresses_Generation_Result::class ) );
					}
				),
			)
		);
		$bitcoin_address_repository = $this->makeEmpty( Bitcoin_Address_Repository::class );

		/** @var API_Background_Jobs_Interface $sut */
		$sut = new Background_Jobs( $api, $bitcoin_address_repository, $logger );

		/** @see Background_Jobs::generate_new_addresses() */
		$sut->generate_new_addresses();
	}

	/**
	 * @covers ::check_new_addresses_for_transactions
	 */
	public function test_check_new_addresses_for_transactions_action(): void {

		$logger                     = new ColorLogger();
		$api                        = $this->makeEmpty(
			API_Background_Jobs_Interface::class,
			array(
				'check_new_addresses_for_transactions' => Expected::once(
					function () {
						return new Check_Assigned_Addresses_For_Transactions_Result();
					}
				),
			)
		);
		$bitcoin_address_repository = $this->makeEmpty( Bitcoin_Address_Repository::class );

		/** @var API_Background_Jobs_Interface $sut */
		$sut = new Background_Jobs( $api, $bitcoin_address_repository, $logger );

		/** @see Background_Jobs::check_new_addresses_for_transactions() */
		$sut->check_new_addresses_for_transactions();

		$this->assertTrue( $logger->hasDebugRecords() );

		$this->markTestIncomplete( 'Assert the function logs a summary of the result.' );
	}

	/**
	 * @covers ::check_assigned_addresses_for_transactions
	 */
	public function test_check_assigned_addresses_for_transactions_action(): void {

		$logger                     = new ColorLogger();
		$api                        = $this->makeEmpty(
			API_Background_Jobs_Interface::class,
			array(
				'check_assigned_addresses_for_transactions' => Expected::once(
					function () {
						return new Check_Assigned_Addresses_For_Transactions_Result();
					}
				),
			)
		);
		$bitcoin_address_repository = $this->makeEmpty( Bitcoin_Address_Repository::class );

		/** @var API_Background_Jobs_Interface $sut */
		$sut = new Background_Jobs( $api, $bitcoin_address_repository, $logger );

		/** @see Background_Jobs::check_assigned_addresses_for_transactions() */
		$sut->check_assigned_addresses_for_transactions();

		$this->assertTrue( $logger->hasInfoRecords() );

		$this->markTestIncomplete( 'Assert the function logs a summary of the result.' );
	}
}
