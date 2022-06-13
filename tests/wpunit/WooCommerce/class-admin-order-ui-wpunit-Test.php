<?php

namespace Nullcorps\WC_Gateway_Bitcoin\WooCommerce;

use BrianHenryIE\ColorLogger\ColorLogger;
use Codeception\Stub\Expected;
use Nullcorps\WC_Gateway_Bitcoin\API\API_Interface;
use PHPUnit\Util\Color;
use WP_Post;

/**
 * @coversDefaultClass \Nullcorps\WC_Gateway_Bitcoin\WooCommerce\Admin_Order_UI
 */
class Admin_Order_UI_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::print_address_transactions_metabox
	 */
	public function test_print_address_transactions_metabox(): void {

		$logger = new ColorLogger();

		$order_details_array = array(
			'btc_total_formatted'           => 'BTC 12345',
			'btc_exchange_rate'             => 12345,
			'btc_address'                   => '1q2w3e4r5t',
			'transactions'                  => array(),
			'btc_amount_received_formatted' => 'BTC 0.01020304',
			'last_checked_time_formatted'   => 'a minute ago',
		);

		$api = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_order_has_bitcoin_gateway' => Expected::once( true ),
				'get_formatted_order_details'  => $order_details_array,
			)
		);

		$sut = new Admin_Order_UI( $api, $logger );

		$order    = new \WC_Order();
		$order_id = $order->save();

		$std_class_post     = new \stdClass();
		$std_class_post->ID = $order_id;

		$post = new WP_Post( $std_class_post );

		$sut->print_address_transactions_metabox( $post );

		$this->markTestIncomplete();

	}

}
