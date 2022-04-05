<?php

namespace Nullcorps\WC_Gateway_Bitcoin\WooCommerce;

use BrianHenryIE\ColorLogger\ColorLogger;
use Codeception\Stub\Expected;
use WC_Payment_Gateway;

/**
 * @coversDefaultClass \Nullcorps\WC_Gateway_Bitcoin\WooCommerce\Payment_Gateways
 */
class Payment_Gateways_Unit_Test extends \Codeception\Test\Unit {

	/**
	 * @covers ::add_to_woocommerce
	 */
	public function test_add_to_woocommerce(): void {

		$logger = new ColorLogger();

		$sut = new Payment_Gateways( $logger );

		$result = $sut->add_to_woocommerce( array() );

		$this->assertContains( WC_Gateway_Bitcoin::class, $result );
	}

	/**
	 * @covers ::add_logger_to_gateways
	 * @covers ::__construct
	 */
	public function test_add_logger_to_gateways(): void {

		$logger = new ColorLogger();

		$sut = new Payment_Gateways( $logger );

		$gateways = array(
			$this->makeEmpty(
				WC_Gateway_Bitcoin::class,
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
