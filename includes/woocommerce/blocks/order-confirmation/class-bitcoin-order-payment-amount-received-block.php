<?php
/**
 * WordPress block for displaying the btc payment received for the WooCommerce order on the order confirmation page.
 *
 * @package    brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce\Blocks\Order_Confirmation;

use BrianHenryIE\WP_Bitcoin_Gateway\Settings_Interface;

class Bitcoin_Order_Payment_Amount_Received_Block {

	public function __construct(
		protected Settings_Interface $settings,
	) {
	}

	/**
	 * @hooked init
	 */
	public function register_block(): void {
		register_block_type(
			$this->settings->get_plugin_dir() . 'assets/js/frontend/woocommerce/blocks/order-confirmation/payment-amount-received/'
		);
	}
}
