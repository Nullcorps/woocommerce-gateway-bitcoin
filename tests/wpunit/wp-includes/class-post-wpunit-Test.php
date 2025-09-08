<?php

namespace BrianHenryIE\WP_Bitcoin_Gateway\WP_Includes;

use BrianHenryIE\WP_Bitcoin_Gateway\API_Interface;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Bitcoin_Gateway\WP_Includes\Post
 */
class Post_WPUnit_Test extends \Codeception\TestCase\WPTestCase {
	protected function setUp(): void {
		parent::setUp();

		global $wp_post_types;
		unset( $wp_post_types['bh-bitcoin-wallet'] );
		unset( $wp_post_types['bh-bitcoin-address'] );
	}

	/**
	 * @covers ::register_wallet_post_type
	 * @covers ::__construct
	 */
	public function test_wallet_inactive_status(): void {

		$api = $this->makeEmpty( API_Interface::class );

		$sut = new Post_BH_Bitcoin_Wallet( $api );

		assert( ! post_type_exists( 'bh-bitcoin-wallet' ) );

		assert( ! in_array( 'inactive', get_available_post_statuses( 'bh-bitcoin-wallet' ), true ) );

		$sut->register_wallet_post_type();

		$this->assertContains( 'inactive', get_available_post_statuses( 'bh-bitcoin-wallet' ) );
	}
}
