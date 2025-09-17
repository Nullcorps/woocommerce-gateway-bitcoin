<?php
/**
 * WordPress block for Bitcoin Gateway exchange rate display.
 *
 * Displays the Bitcoin exchange rate from order meta key.
 *
 * @package brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce\Blocks\Order_Confirmation;

use BrianHenryIE\WP_Bitcoin_Gateway\API_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\Settings_Interface;

class Bitcoin_Exchange_Rate_Block {

	public function __construct(
		protected API_Interface $api,
		protected Settings_Interface $settings,
	) {
	}

	/**
	 * @hooked init
	 */
	public function register_block(): void {
		register_block_type(
			$this->settings->get_plugin_dir() . 'assets/js/frontend/woocommerce/blocks/order-confirmation/exchange-rate/'
		);
	}
}
