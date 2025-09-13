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

		/** @var array{dependencies:array<string>, version:string} $webpack_asset_frontend */
		$webpack_asset_frontend = include $this->settings->get_plugin_dir() . 'assets/js/frontend/woocommerce/blocks/order-confirmation/exchange-rate/exchange-rate-block.min.asset.php';
		wp_register_script(
			'bh-wp-bitcoin-gateway-exchange-rate-block',
			$this->settings->get_plugin_url() . 'assets/js/frontend/woocommerce/blocks/order-confirmation/exchange-rate/exchange-rate-block.min.js',
			$webpack_asset_frontend['dependencies'],
			$webpack_asset_frontend['version'],
			true
		);

		/** @var array{dependencies:array<string>, version:string} $webpack_asset_editor */
		$webpack_asset_editor = include $this->settings->get_plugin_dir() . 'assets/js/frontend/woocommerce/blocks/order-confirmation/exchange-rate/exchange-rate-admin.min.asset.php';
		wp_register_script(
			'bh-wp-bitcoin-gateway-exchange-rate-admin',
			$this->settings->get_plugin_url() . 'assets/js/frontend/woocommerce/blocks/order-confirmation/exchange-rate/exchange-rate-admin.min.js',
			$webpack_asset_editor['dependencies'],
			$webpack_asset_editor['version'],
			true
		);

		register_block_type(
			'bh-wp-bitcoin-gateway/exchange-rate',
			array(
				'editor_script' => 'bh-wp-bitcoin-gateway-exchange-rate-admin',
				'attributes'    => array(
					'orderId'   => array(
						'type'    => 'number',
						'default' => 0,
					),
					'showLabel' => array(
						'type'    => 'boolean',
						'default' => true,
					),
				),
				'uses_context'  => array(
					'bh-wp-bitcoin-gateway/btc_exchange_rate_formatted',
					'bh-wp-bitcoin-gateway/exchange_rate_url',
				),
				'view_script'   => 'bh-wp-bitcoin-gateway-exchange-rate-block',
			)
		);
	}
}
