<?php

namespace Nullcorps\WC_Gateway_Bitcoin\WooCommerce;

/**
 * @coversDefaultClass \Nullcorps\WC_Gateway_Bitcoin\WooCommerce\Payment_Gateways
 */
class Payment_Gateways_Unit_Test extends \Codeception\Test\Unit {

	/**
	 * @covers ::add_to_woocommerce
	 */
	public function test_add_to_woocommerce(): void {

		$sut = new Payment_Gateways();

		$result = $sut->add_to_woocommerce( array() );

		$this->assertContains( WC_Gateway_Bitcoin::class, $result );
	}

}
