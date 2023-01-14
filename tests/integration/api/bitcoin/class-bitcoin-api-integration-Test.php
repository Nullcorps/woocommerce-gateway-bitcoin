<?php
/**
 * All tests on classes implementing the Bitcoin_API_Interface should return the same values, so use a dataprovider.
 */


namespace BrianHenryIE\WC_Bitcoin_Gateway\API\Blockchain;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Bitcoin_Gateway\API\Blockchain_API_Interface;

/**
 * @coversNothing
 */
class Bitcoin_API_Integration_Test extends \Codeception\TestCase\WPTestCase {

	public function get_bitcoin_apis(): array {
		$result = array();

		$logger = new ColorLogger();

		$result[] = array( new SoChain_API() );
		$result[] = array( new Blockchain_Info_API( $logger ) );
		$result[] = array( new Blockstream_Info_API( $logger ) );

		return $result;
	}

	/**
	 * @dataProvider get_bitcoin_apis
	 */
	public function test_get_transactions_received( Blockchain_API_Interface $sut ): void {
		$logger = new ColorLogger();

		$sent_to = '3KKUGZk4yU9QfZZA9y9K5MkwBX7Rozaaum';

		$result = $sut->get_transactions_received( $sent_to );

		$logger->info( get_class( $sut ) );
		$logger->info( json_encode( $result ) );

		$first_transaction = array_pop( $result );

		// Verify all APIs are returning the time as a DateTime.
		$this->assertInstanceOf( \DateTimeInterface::class, $first_transaction['time'] );

		// Verify the value is returned as a string.
		$this->assertIsString( $first_transaction['value'] );

		// Verify all APIs return the number of confirmations for the transactions.
		$this->assertArrayHasKey( 'confirmations', $first_transaction );
	}
}
