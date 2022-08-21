<?php

namespace Nullcorps\WC_Gateway_Bitcoin\WooCommerce;

use BrianHenryIE\ColorLogger\ColorLogger;
use Nullcorps\WC_Gateway_Bitcoin\API_Interface;
use WC_Gateway_BACS;

/**
 * @coversDefaultClass \Nullcorps\WC_Gateway_Bitcoin\WooCommerce\Payment_Gateways
 */
class Payment_Gateways_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::filter_to_only_bitcoin_gateways
	 */
	public function test_filter_to_only_bitcoin_gateways(): void {

		$logger = new ColorLogger();

		$sut = new Payment_Gateways( $logger );

		$GLOBALS['nullcorps_wc_gateway_bitcoin'] = $this->makeEmpty( API_Interface::class );

		$gateways = array(
			new WC_Gateway_BACS(),
			new WC_Gateway_Bitcoin(),
			new class() extends WC_Gateway_Bitcoin {
				/**
				 * Unique id for second instance.
				 *
				 * @var string
				 */
				public $id = 'bitcoin_gateway_2';
			},
			'nothing',
		);

		// Pretend we're on the gateways list page, with parameter `class=nullcorps-wc-gateway-bitcoin`.
		$GLOBALS['current_tab'] = 'checkout';
		$_GET['class']          = 'nullcorps-wc-gateway-bitcoin';

		$result = $sut->filter_to_only_bitcoin_gateways( $gateways );

		$this->assertCount( 2, $result );
	}

	/**
	 * @covers ::filter_to_only_bitcoin_gateways
	 */
	public function test_filter_to_only_bitcoin_gateways_wrong_page(): void {

		$logger = new ColorLogger();

		$sut = new Payment_Gateways( $logger );

		$gateways = array(
			new WC_Gateway_BACS(),
			new WC_Gateway_Bitcoin(),
		);

		$result = $sut->filter_to_only_bitcoin_gateways( $gateways );

		$this->assertCount( 2, $result );

	}
}
