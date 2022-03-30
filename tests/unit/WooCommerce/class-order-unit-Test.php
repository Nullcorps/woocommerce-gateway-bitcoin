<?php

namespace Nullcorps\WC_Gateway_Bitcoin\WooCommerce;

/**
 * @coversDefaultClass \Nullcorps\WC_Gateway_Bitcoin\WooCommerce\Order
 */
class Order_Unit_Test extends \Codeception\Test\Unit {

	/**
	 * TODO: Find a better meta key.
	 */
	public function test_verify_const(): void {
		$this->assertEquals( 'woobtc_address', Order::BITCOIN_ADDRESS_META_KEY );
	}
}
