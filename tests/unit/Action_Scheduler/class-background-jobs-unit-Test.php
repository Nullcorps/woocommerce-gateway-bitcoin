<?php

namespace Nullcorps\WC_Gateway_Bitcoin\Action_Scheduler;

use BrianHenryIE\ColorLogger\ColorLogger;
use Codeception\Stub\Expected;
use Nullcorps\WC_Gateway_Bitcoin\API\API_Interface;

/**
 * @coversDefaultClass \Nullcorps\WC_Gateway_Bitcoin\Action_Scheduler\Background_Jobs
 */
class Background_Jobs_Unit_Test extends \Codeception\Test\Unit {

	/**
	 * @covers ::generate_new_addresses
	 * @covers ::__construct
	 */
	public function test_generate_new_adresses(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty(
			API_Interface::class,
			array(
				'generate_new_addresses' => Expected::once(
					function() {
						return array();
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
					function() {
						return array();
					}
				),
			)
		);

		$sut = new Background_Jobs( $api, $logger );

		$sut->check_new_addresses_for_transactions();

		$this->assertTrue( $logger->hasDebugRecords() );

		// TODO:
		// $this->assertTrue( $logger->hasInfoThatContains( '' ) );
	}

}
