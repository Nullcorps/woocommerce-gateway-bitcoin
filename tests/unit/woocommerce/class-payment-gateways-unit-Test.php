<?php

namespace BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Bitcoin_Gateway\API_Interface;
use Codeception\Stub\Expected;
use WC_Payment_Gateway;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\Payment_Gateways
 */
class Payment_Gateways_Unit_Test extends \Codeception\Test\Unit {

	/**
	 * @covers ::add_to_woocommerce
	 */
	public function test_add_to_woocommerce(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_server_has_dependencies' => Expected::once( true ),
			)
		);

		$sut = new Payment_Gateways( $api, $logger );

		$result = $sut->add_to_woocommerce( array() );

		$this->assertContains( Bitcoin_Gateway::class, $result );
	}

	/**
	 * @covers ::add_logger_to_gateways
	 * @covers ::__construct
	 */
	public function test_add_logger_to_gateways(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty( API_Interface::class );

		$sut = new Payment_Gateways( $api, $logger );

		$gateways = array(
			$this->makeEmpty(
				Bitcoin_Gateway::class,
				array(
					'set_logger' => Expected::once(
						function ( $the_logger ) use ( $logger ) {
							assert( $the_logger === $logger );
						}
					),
				)
			),
			$this->makeEmpty(
				WC_Payment_Gateway::class,
				array(
					'set_logger' => Expected::never(),
				)
			),
		);

		$sut->add_logger_to_gateways( $gateways );
	}

}
