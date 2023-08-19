<?php

namespace BrianHenryIE\WP_Bitcoin_Gateway\API\Model;

use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address_Factory;
use BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce\Order;
use Codeception\Stub\Expected;
use DateTimeImmutable;
use WC_Order;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Bitcoin_Gateway\API\Model\Bitcoin_Order
 */
class Bitcoin_Order_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::__construct
	 */
	public function test_get_id(): void {
		$bitcoin_address_mock    = self::make( Bitcoin_Address::class );
		$bitcoin_address_factory = self::make(
			Bitcoin_Address_Factory::class,
			array(
				'get_post_id_for_address' => Expected::once( 123 ),
				'get_by_post_id'          => Expected::once( $bitcoin_address_mock ),
			)
		);

		$order = new WC_Order();
		$order->set_payment_method( 'bitcoin' );
		$order->add_meta_data( Order::BITCOIN_ADDRESS_META_KEY, 'xpub-address', true );
		$order->add_meta_data( Order::EXCHANGE_RATE_AT_TIME_OF_PURCHASE_META_KEY, 1234, true );
		$order->add_meta_data( Order::ORDER_TOTAL_BITCOIN_AT_TIME_OF_PURCHASE_META_KEY, 0.01, true );
		$order->add_meta_data( Order::LAST_CHECKED_META_KEY, new \DateTime(), true );
		$order_id = $order->save();

		$sut = new Bitcoin_Order( $order, $bitcoin_address_factory );

		$result = $sut->get_id();

		self::assertEquals( $order_id, $result );
	}

	/**
	 * @covers ::get_address
	 */
	public function test_get_address(): void {
		$bitcoin_address_mock    = self::make(
			Bitcoin_Address::class,
			array( 'get_raw_address' => Expected::once( 'success' ) )
		);
		$bitcoin_address_factory = self::make(
			Bitcoin_Address_Factory::class,
			array(
				'get_post_id_for_address' => Expected::once( 123 ),
				'get_by_post_id'          => Expected::once( $bitcoin_address_mock ),
			)
		);

		$order = new WC_Order();
		$order->set_payment_method( 'bitcoin' );
		$order->add_meta_data( Order::BITCOIN_ADDRESS_META_KEY, 'xpub-address', true );
		$order->add_meta_data( Order::EXCHANGE_RATE_AT_TIME_OF_PURCHASE_META_KEY, 1234, true );
		$order->add_meta_data( Order::ORDER_TOTAL_BITCOIN_AT_TIME_OF_PURCHASE_META_KEY, 0.01, true );
		$order->add_meta_data( Order::LAST_CHECKED_META_KEY, new \DateTime(), true );
		$order_id = $order->save();

		$sut = new Bitcoin_Order( $order, $bitcoin_address_factory );

		$result = $sut->get_address();

		self::assertEquals( 'success', $result->get_raw_address() );
	}

	/**
	 * No covers because it uses a __call @method.
	 */
	public function test_is_paid(): void {
		$bitcoin_address_mock    = self::make( Bitcoin_Address::class );
		$bitcoin_address_factory = self::make(
			Bitcoin_Address_Factory::class,
			array(
				'get_post_id_for_address' => Expected::once( 123 ),
				'get_by_post_id'          => Expected::once( $bitcoin_address_mock ),
			)
		);

		$order = self::make(
			WC_Order::class,
			array(
				'is_paid' => Expected::once( true ),
			)
		);
		$order->set_payment_method( 'bitcoin' );
		$order->add_meta_data( Order::BITCOIN_ADDRESS_META_KEY, 'xpub-address', true );
		$order->add_meta_data( Order::EXCHANGE_RATE_AT_TIME_OF_PURCHASE_META_KEY, 1234, true );
		$order->add_meta_data( Order::ORDER_TOTAL_BITCOIN_AT_TIME_OF_PURCHASE_META_KEY, 0.01, true );
		$order->add_meta_data( Order::LAST_CHECKED_META_KEY, new \DateTime(), true );
		$order_id = $order->save();

		$sut = new Bitcoin_Order( $order, $bitcoin_address_factory );

		$result = $sut->is_paid();

		self::assertEquals( true, $result );
	}

	/**
	 * @covers ::set_last_checked_time
	 */
	public function test_set_last_checked_time(): void {
		$bitcoin_address_mock    = self::make( Bitcoin_Address::class );
		$bitcoin_address_factory = self::make(
			Bitcoin_Address_Factory::class,
			array(
				'get_post_id_for_address' => Expected::once( 123 ),
				'get_by_post_id'          => Expected::once( $bitcoin_address_mock ),
			)
		);

		$order = new WC_Order();
		$order->set_payment_method( 'bitcoin' );
		$order->add_meta_data( Order::BITCOIN_ADDRESS_META_KEY, 'xpub-address', true );
		$order->add_meta_data( Order::EXCHANGE_RATE_AT_TIME_OF_PURCHASE_META_KEY, 1234, true );
		$order->add_meta_data( Order::ORDER_TOTAL_BITCOIN_AT_TIME_OF_PURCHASE_META_KEY, 0.01, true );
		$order->add_meta_data( Order::LAST_CHECKED_META_KEY, new DateTimeImmutable(), true );
		$order_id = $order->save();

		$sut = new Bitcoin_Order( $order, $bitcoin_address_factory );

		// 946684800 is Y2K.
		$last_checked = DateTimeImmutable::createFromFormat( 'U', 946684800 );

		$sut->set_last_checked_time( $last_checked );
		$sut->save();

		$order = wc_get_order( $order_id );
		/** @var \DateTimeInterface $result */
		$result = $order->get_meta( Order::LAST_CHECKED_META_KEY, true );

		self::assertEquals( 946684800, $result->format( 'U' ) );
	}

	/**
	 * No covers because it uses a __call @method.
	 */
	public function test_get_status(): void {
		$bitcoin_address_mock    = self::make( Bitcoin_Address::class );
		$bitcoin_address_factory = self::make(
			Bitcoin_Address_Factory::class,
			array(
				'get_post_id_for_address' => Expected::once( 123 ),
				'get_by_post_id'          => Expected::once( $bitcoin_address_mock ),
			)
		);

		$order = new WC_Order();
		$order->set_payment_method( 'bitcoin' );
		$order->set_status( 'on-hold' );
		$order->add_meta_data( Order::BITCOIN_ADDRESS_META_KEY, 'xpub-address', true );
		$order->add_meta_data( Order::EXCHANGE_RATE_AT_TIME_OF_PURCHASE_META_KEY, 1234, true );
		$order->add_meta_data( Order::ORDER_TOTAL_BITCOIN_AT_TIME_OF_PURCHASE_META_KEY, 0.01, true );
		$order->add_meta_data( Order::LAST_CHECKED_META_KEY, new \DateTime(), true );
		$order_id = $order->save();

		$sut = new Bitcoin_Order( $order, $bitcoin_address_factory );

		$result = $sut->get_status();

		self::assertEquals( 'on-hold', $result );
	}
}
