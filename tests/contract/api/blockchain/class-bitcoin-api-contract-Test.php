<?php
/**
 * All tests on classes implementing the Bitcoin_API_Interface should return the same values, so use a dataprovider.
 *
 * This should be in the Contracts test folder.
 *
 * @package brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\API\Blockchain;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Blockchain_API_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Model\Transaction_Interface;

/**
 * @coversNothing
 */
class Bitcoin_API_Contract_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @return array<Blockchain_API_Interface[]>
	 */
	public function get_bitcoin_apis(): array {
		$result = array();

		$logger = new ColorLogger();

		$result[] = array( new Blockchain_Info_API( $logger ) );
		$result[] = array( new Blockstream_Info_API( $logger ) );

		return $result;
	}

	/**
	 * @dataProvider get_bitcoin_apis
	 *
	 * @param Blockchain_API_Interface $sut An instance of the API we are testing.
	 */
	public function test_get_transactions_received( Blockchain_API_Interface $sut ): void {
		$logger = new ColorLogger();

		$sent_to = '3KKUGZk4yU9QfZZA9y9K5MkwBX7Rozaaum';

		$result = $sut->get_transactions_received( $sent_to );

		$logger->info( get_class( $sut ) );
		$logger->info( wp_json_encode( $result, JSON_THROW_ON_ERROR ) );

		/** @var Transaction_Interface $first_transaction */
		$first_transaction = array_pop( $result );

		self::assertEquals( '882dccf5a828a62ecc42c1251b3086ad4f315ef6864653e01f3e64a1793555bd', $first_transaction->get_txid() );
		self::assertEquals( 0.00730728, $first_transaction->get_value( $sent_to ) );
		self::assertEquals( '686306', $first_transaction->get_block_height() );
		self::assertEquals( 1622852486, $first_transaction->get_time()->getTimestamp() );

	}
}
