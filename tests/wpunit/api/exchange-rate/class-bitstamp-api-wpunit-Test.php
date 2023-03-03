<?php

namespace BrianHenryIE\WP_Bitcoin_Gateway\API\Exchange_Rate;

use BrianHenryIE\ColorLogger\ColorLogger;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Bitcoin_Gateway\API\Exchange_Rate\Bitstamp_API
 */
class Bitstamp_API_Integration_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::get_exchange_rate
	 * @covers ::__construct
	 */
	public function test_query_api():void {

		$logger = new ColorLogger();

		$sut = new Bitstamp_API( $logger );

		$request_response = array(
			'body'     => '{"high": "41497.24", "last": "41008.81", "timestamp": "1647557805", "bid": "40974.06", "vwap": "40893.19", "volume": "1679.79539059", "low": "40449.06", "ask": "40992.76", "open": "41142.76"}',
			'response' =>
				array(
					'code'    => 200,
					'message' => 'OK',
				),
		);

		add_filter(
			'pre_http_request',
			function() use ( $request_response ) {
				return $request_response;
			}
		);

		$result = $sut->get_exchange_rate( 'usd' );

		$this->assertEquals( '41008.81', $result );

	}
}
