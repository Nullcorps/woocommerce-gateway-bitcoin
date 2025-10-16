<?php

namespace BrianHenryIE\WP_Bitcoin_Gateway\Action_Scheduler;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Model\Addresses_Generation_Result;
use Codeception\Stub\Expected;
use BrianHenryIE\WP_Bitcoin_Gateway\API_Interface;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Bitcoin_Gateway\Action_Scheduler\Background_Jobs
 */
class Background_Jobs_Unit_Test extends \Codeception\Test\Unit {

	/**
	 * @covers ::generate_new_addresses
	 * @covers ::__construct
	 */
	public function test_generate_new_adresses_hooked_action(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty(
			API_Interface::class,
			array(
				'generate_new_addresses' => Expected::once(
					function () {
						return array( $this->createMock( Addresses_Generation_Result::class ) );
					}
				),
			)
		);

		$sut = new Background_Jobs( $api, $logger );

		$sut->generate_new_addresses();
	}

	/**
	 * @covers ::check_new_addresses_for_transactions
	 */
	public function test_check_new_addresses_for_transactions(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty(
			API_Interface::class,
			array(
				'check_new_addresses_for_transactions' => Expected::once(
					function () {
						return array();
					}
				),
			)
		);

		$sut = new Background_Jobs( $api, $logger );

		$sut->check_new_addresses_for_transactions();

		$this->assertTrue( $logger->hasDebugRecords() );

		$this->markTestIncomplete( 'Assert the function logs a summary of the result.' );
	}
}
