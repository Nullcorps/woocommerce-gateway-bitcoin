<?php

namespace BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use BrianHenryIE\WP_Bitcoin_Gateway\Settings_Interface;
use Codeception\Stub\Expected;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce\HPOS
 */
class HPOS_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::declare_compatibility
	 */
	public function test_declare_compatibility(): void {

		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_basename' => Expected::once( 'bh-wp-bitcoin-gateway/bh-wp-bitcoin-gateway.php' ),
			)
		);

		/**
		 * `doing_action('before_woocommerce_init')` must be true.
		 */
		global $wp_current_filter;
		$wp_current_filter[] = 'before_woocommerce_init';

		$sut = new HPOS( $settings );

		$sut->declare_compatibility();

		$result = FeaturesUtil::get_compatible_plugins_for_feature( 'custom_order_tables' );

		$this->assertContains( 'bh-wp-bitcoin-gateway/bh-wp-bitcoin-gateway.php', $result['compatible'], wp_json_encode( $result['compatible'] ) );
	}

}
