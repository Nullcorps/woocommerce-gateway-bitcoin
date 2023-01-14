<?php

namespace BrianHenryIE\WC_Bitcoin_Gateway\API\Blockchain;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Bitcoin_Gateway\API\Blockchain\SoChain_API
 */
class SoChain_API_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::get_address_balance
	 */
	public function test_get_address_balance():void {

		// The pizza address.
		$address = '1XPTgDRhN8RFnzniWCddobD9iKZatrvH4';

		$sut = new SoChain_API();

		$request_response = array(
			'body'     => wp_json_encode(
				array(
					'status' => 'success',
					'data'   =>
						array(
							'network'             => 'BTC',
							'address'             => '1XPTgDRhN8RFnzniWCddobD9iKZatrvH4',
							'confirmed_balance'   => '0.00018142',
							'unconfirmed_balance' => '0.00000000',
						),
				)
			),
			'response' => array(
				'code' => 200,
			),
		);

		add_filter(
			'pre_http_request',
			function() use ( $request_response ) {
				return $request_response;
			}
		);

		$result = $sut->get_address_balance( $address, 0 );

		$this->assertEquals( '0.00018142', $result['confirmed_balance'] );
	}

	/**
	 * @covers ::get_address_balance
	 */
	public function test_get_address_balance_bad_address(): void {

		$address = 'not-a-valid-address';

		$sut = new SoChain_API();

		$request_response = array(
			'body'     => wp_json_encode(
				array(
					'status' => 'fail',
					'data'   =>
						array(
							'network'       => 'Network is required (DOGE, DOGETEST, ...)',
							'address'       => 'A valid address is required',
							'confirmations' => 'Minimum number of confirmations (optional)',
						),
				)
			),
			'response' => array(
				'code' => 200,
			),
		);
		add_filter(
			'pre_http_request',
			function() use ( $request_response ) {
				return $request_response;
			}
		);

		$exception = null;

		try {
			$result = $sut->get_address_balance( $address, 0 );
		} catch ( \Exception $e ) {
			$exception = $e;
		}

		$this->assertNotNull( $exception );
	}

	/**
	 * @covers ::get_transactions_received
	 */
	public function test_get_transactions_received(): void {

		// The pizza address.
		$address = '1XPTgDRhN8RFnzniWCddobD9iKZatrvH4';

		$sut = new SoChain_API();

		global $project_root_dir;
		$data             = include $project_root_dir . '/tests/_data/sochain-test-get-transactions-received.php';
		$request_response = array(
			'body'     => wp_json_encode(
				$data
			),
			'response' => array(
				'code' => 200,
			),
		);

		add_filter(
			'pre_http_request',
			function() use ( $request_response ) {
				return $request_response;
			}
		);

		$result = $sut->get_transactions_received( $address );

		$this->assertArrayHasKey( '4d4b8aeb24fb72ba08989a86148c948ca23227e7d988a299ac4db03c9797a241', $result );
	}
}
