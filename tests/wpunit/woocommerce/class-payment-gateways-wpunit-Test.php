<?php

namespace BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Bitcoin_Gateway\API_Interface;
use BrianHenryIE\WC_Bitcoin_Gateway\Settings_Interface;
use Codeception\Stub\Expected;
use WC_Gateway_BACS;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\Payment_Gateways
 */
class Payment_Gateways_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::add_to_woocommerce
	 */
	public function test_add_to_woocommerce(): void {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );
		$api      = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_server_has_dependencies' => Expected::once( true ),
			)
		);

		$sut = new Payment_Gateways( $api, $settings, $logger );

		$result = $sut->add_to_woocommerce( array() );

		$this->assertEquals( Bitcoin_Gateway::class, $result[0] );
	}

	/**
	 * @covers ::filter_to_only_bitcoin_gateways
	 */
	public function test_filter_to_only_bitcoin_gateways(): void {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );

		$sut = new Payment_Gateways( $api, $settings, $logger );

		$GLOBALS['bh_wc_bitcoin_gateway'] = $this->makeEmpty( API_Interface::class );

		$gateways = array(
			new WC_Gateway_BACS(),
			new Bitcoin_Gateway(),
			new class() extends Bitcoin_Gateway {
				/**
				 * Unique id for second instance.
				 *
				 * @var string
				 */
				public $id = 'bitcoin_gateway_2';
			},
			'nothing',
		);

		// Pretend we're on the gateways list page, with parameter `class=bh-wc-bitcoin-gateway`.
		$GLOBALS['current_tab'] = 'checkout';
		$_GET['class']          = 'bh-wc-bitcoin-gateway';

		$result = $sut->filter_to_only_bitcoin_gateways( $gateways );

		$this->assertCount( 2, $result );
	}

	/**
	 * @covers ::filter_to_only_bitcoin_gateways
	 */
	public function test_filter_to_only_bitcoin_gateways_wrong_page(): void {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );

		$sut = new Payment_Gateways( $api, $settings, $logger );

		$gateways = array(
			new WC_Gateway_BACS(),
			new Bitcoin_Gateway(),
		);

		$result = $sut->filter_to_only_bitcoin_gateways( $gateways );

		$this->assertCount( 2, $result );
	}
}
