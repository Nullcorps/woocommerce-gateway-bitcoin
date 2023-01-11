<?php

namespace Nullcorps\WC_Gateway_Bitcoin\API\Address_Storage;

/**
 * @coversDefaultClass \Nullcorps\WC_Gateway_Bitcoin\API\Address_Storage\Crypto_Address
 */
class Crypto_Address_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * When using `update_post_meta()` the last modified time of the post does not change. This
	 * is a test to see will it update if we use `update_post( array( 'meta_input' => array() )` instead.
	 *
	 * @covers ::set_order_id
	 */
	public function test_last_modified_time_is_updated(): void {

		$crypto_address_factory = new Crypto_Address_Factory();

		$wallet = $this->makeEmpty( Crypto_Wallet::class );

		$crypto_address_post_id = $crypto_address_factory->save_new( 'address', 2, $wallet );

		/** @var \WP_Post $crypto_address_post */
		$crypto_address_post = get_post( $crypto_address_post_id );

		$last_modified_time_before = $crypto_address_post->post_modified_gmt;

		$crypto_address_object = $crypto_address_factory->get_by_post_id( $crypto_address_post_id );

		sleep( 1 );

		$crypto_address_object->set_order_id( 123 );

		/** @var \WP_Post $crypto_address_post */
		$crypto_address_post = get_post( $crypto_address_post_id );

		$last_modified_time_after = $crypto_address_post->post_modified_gmt;

		$this->assertNotEquals( $last_modified_time_before, $last_modified_time_after );

	}

	/**
	 * @covers ::get_order_id
	 */
	public function test_get_order_id_null_before_set(): void {

		$crypto_address_factory = new Crypto_Address_Factory();

		$wallet = $this->makeEmpty( Crypto_Wallet::class );

		$crypto_address_post_id = $crypto_address_factory->save_new( 'address', 2, $wallet );

		$sut = $crypto_address_factory->get_by_post_id( $crypto_address_post_id );

		$result = $sut->get_order_id();

		$this->assertNull( $result );

	}

	/**
	 * @covers ::get_order_id
	 */
	public function test_get_order_id_after_set(): void {

		$crypto_address_factory = new Crypto_Address_Factory();

		$wallet = $this->makeEmpty( Crypto_Wallet::class );

		$crypto_address_post_id = $crypto_address_factory->save_new( 'address', 2, $wallet );

		$sut = $crypto_address_factory->get_by_post_id( $crypto_address_post_id );

		$sut->set_order_id( 123 );

		$sut = $crypto_address_factory->get_by_post_id( $crypto_address_post_id );

		$result = $sut->get_order_id();

		$this->assertEquals( 123, $result );
	}

	/**
	 * @covers ::set_status
	 */
	public function test_set_status(): void {

		$crypto_address_factory = new Crypto_Address_Factory();

		$wallet = $this->makeEmpty( Crypto_Wallet::class );

		$crypto_address_post_id = $crypto_address_factory->save_new( 'address', 2, $wallet );

		$sut = $crypto_address_factory->get_by_post_id( $crypto_address_post_id );

		$sut->set_status( 'assigned' );

		$sut = $crypto_address_factory->get_by_post_id( $crypto_address_post_id );

		$result = $sut->get_status();

		$this->assertEquals( 'assigned', $result );
	}

	/**
	 * @covers ::get_balance
	 */
	public function test_get_balance_used(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'bh-crypto-address',
				'post_status' => 'used',
				'meta_input'  => array(
					Crypto_Address::BALANCE_META_KEY => '1.23456789',
				),
			)
		);

		$crypto_address_factory = new Crypto_Address_Factory();

		$sut = $crypto_address_factory->get_by_post_id( $post_id );

		$result = $sut->get_balance();

		$this->assertEquals( '1.23456789', $result );
	}

	/**
	 * @covers ::get_balance
	 */
	public function test_get_balance_unused(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'bh-crypto-address',
				'post_status' => 'unused',
			)
		);

		$crypto_address_factory = new Crypto_Address_Factory();

		$sut = $crypto_address_factory->get_by_post_id( $post_id );

		$result = $sut->get_balance();

		$this->assertEquals( '0.0', $result );
	}

	/**
	 * @covers ::get_balance
	 */
	public function test_get_balance_unknown(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'bh-crypto-address',
				'post_status' => 'unknown',
			)
		);

		$crypto_address_factory = new Crypto_Address_Factory();

		$sut = $crypto_address_factory->get_by_post_id( $post_id );

		$result = $sut->get_balance();

		$this->assertNull( $result );
	}

}
