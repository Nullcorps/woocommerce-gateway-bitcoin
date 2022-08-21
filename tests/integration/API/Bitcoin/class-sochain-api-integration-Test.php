<?php



namespace Nullcorps\WC_Gateway_Bitcoin\API\Bitcoin;

use BrianHenryIE\ColorLogger\ColorLogger;

/**
 * @coversNothing
 */
class SoChain_API_Integration_Test extends \Codeception\TestCase\WPTestCase {

	public function test_query_address(): void {

		$sut = new SoChain_API();

		$sent_to = '1N8nUbuZaiPBH5eh5eVHvx3zpnUfs2JoR8';

		$sut->get_address_balance( $sent_to, 0 );

	}

	public function test_transaction(): void {

		$sut = new SoChain_API();

		$sent_to = '1N8nUbuZaiPBH5eh5eVHvx3zpnUfs2JoR8';

		// 0.5 BTC from CoinJar to Jaxx
		$sent_to = '1BnyWeeS93w8UAgVdeyvk1TddJDWNmDhcp';

		// From UB to CoinJar.
		$sent_to = '3PAGXbstegwh6wtGnYLnn2UULK5KCVuK4P';

		$sent_to = '3KKUGZk4yU9QfZZA9y9K5MkwBX7Rozaaum';

		$sut->get_transactions_received( $sent_to );

	}
}
