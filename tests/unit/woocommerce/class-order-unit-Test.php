<?php

namespace BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\Order
 */
class Order_Unit_Test extends \Codeception\Test\Unit {

	/**
	 * TODO: Find a better meta key.
	 */
	public function test_verify_const(): void {
		$this->assertEquals( 'bh_wc_bitcoin_gateway_address', Order::BITCOIN_ADDRESS_META_KEY );
	}
}
