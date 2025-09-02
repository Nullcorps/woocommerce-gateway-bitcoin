<?php
/**
 * WordPress block for Bitcoin Gateway exchange rate display.
 *
 * Displays the Bitcoin exchange rate from order meta key.
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce;

use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address_Factory;
use BrianHenryIE\WP_Bitcoin_Gateway\API_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\Settings_Interface;
use WC_Order;

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

		wp_register_script(
			'bh-wp-bitcoin-gateway-exchange-rate-block-editor',
			$this->settings->get_plugin_url() . 'assets/js/frontend/blocks/order-confirmation/exchange-rate/bh-wp-bitcoin-gateway-exchange-rate.min.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			$this->settings->get_plugin_version(),
			true
		);

		register_block_type(
			'bh-wp-bitcoin-gateway/exchange-rate',
			array(
				'editor_script'   => 'bh-wp-bitcoin-gateway-exchange-rate-block-editor',
				'attributes'      => array(
					'orderId'   => array(
						'type'    => 'number',
						'default' => 0,
					),
					'showLabel' => array(
						'type'    => 'boolean',
						'default' => true,
					),
				),
//				'render_callback' => array( $this, 'render_block' ),
				'uses_context'    => array( 'bh-wp-bitcoin-gateway/orderId' ),
			)
		);
	}

	/**
	 * TODO: hopefully this isn't needed once inside the container block
	 *
	 * Render callback for the bitcoin-order block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string Rendered block content.
	 */
	public function render_block( array $attributes, string $content, \WP_Block $block ): string {

		/**
		 * Relies on the `render_block_context` filter in {@see Bitcoin_Order_Confirmation_Block}
		 */
		$bc_order_id = $block->context['bh-wp-bitcoin-gateway/orderId'] ?? 0;

		// If we don't have an order, return nothing.
		if ( empty( $bc_order_id ) ) {
			return '';
		}

		$wc_order = wc_get_order( $bc_order_id );

		if ( ! ( $wc_order instanceof WC_Order ) ) {
			return $content;
		}

		$bitcoin_order = $this->api->get_order_details( $wc_order );

		// TODO: How to render here using the JS?!
		$rate = '<span>' . $bitcoin_order->get_btc_exchange_rate() . '</span>';

		return $content . $rate;
	}
}
