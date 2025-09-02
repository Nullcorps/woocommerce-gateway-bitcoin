<?php
/**
 * WordPress block for Bitcoin Gateway order container.
 *
 * A container block that provides order context to inner blocks on WooCommerce Thank You pages.
 *
 * @package brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce\Blocks;

use BrianHenryIE\WP_Bitcoin_Gateway\Settings_Interface;
use WP_Block_Type_Registry;

class Bitcoin_Order_Confirmation_Block {

	public function __construct(
		protected Settings_Interface $settings
	) {
	}

	/**
	 *
	 * Hooking on `init` so even if WooCommerce is disabled, the blocks are still available for design.
	 *
	 * @hooked init
	 */
	public function register_block(): void {

		wp_register_script(
			'bh-wp-bitcoin-gateway-bitcoin-order-block',
			$this->settings->get_plugin_url() . 'assets/js/frontend/woocommerce/blocks/order-confirmation/bitcoin-order-confirmation-group/bitcoin-order-confirmation-group.min.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			// $this->settings->get_plugin_version(),
			time(), // TODO: do we need to bust cache now the `...min.asset.php` file has a version?
			array( 'in_footer' => true )
		);

		register_block_type(
			// TODO: rename to be explicitly a block for WooCommerce.
			'bh-wp-bitcoin-gateway/bitcoin-order',
			array(
				'editor_script'    => 'bh-wp-bitcoin-gateway-bitcoin-order-block',
				'attributes'       => array(
					'orderId' => array(
						'type'    => 'number',
						'default' => 0,
					),
				),
				'provides_context' => array(
					'bh-wp-bitcoin-gateway/orderId' => 'orderId',
				),
				'render_callback'  => array( $this, 'render_block' ),
			)
		);

		// TODO: move out of here for legibility.
		add_filter(
			'render_block_context',
			array( $this, 'add_order_id_context' ),
			10,
			3
		);
	}

	/**
	 * Render callback for the bitcoin-order block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string Rendered block content.
	 */
	public function render_block( array $attributes, string $content, $block ): string {
		$order_id = $attributes['orderId'] ?? 0;

		if ( 0 === $order_id ) {
			$order_id = $this->detect_order_id();
		}

		// Update the block context with the detected order ID.
		if ( $order_id > 0 ) {
			// TODO: This doesn't seem to do anything for PHP rendered blocks... does it help for JS rendered?
			$block->context['bh-wp-bitcoin-gateway/orderId'] = $order_id;
		}

		// Return the inner blocks content wrapped in our container.
		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class'         => 'bh-wp-bitcoin-gateway-bitcoin-order-container',
				'data-order-id' => $order_id,
			)
		);

		// TODO: I thought this would re-render the inner blocks, which were rendered before this block determined the order id it was wrapping them with.
		// $block->refresh_parsed_block_dependents();
		// $block->refresh_context_dependents();

		return sprintf(
			'<div %1$s><div class="wp-block-group"><div class="wp-block-group__inner-container">%2$s</div></div></div>',
			$wrapper_attributes,
			// $block->render() // TODO: This resulted in an infinite loop that didn't use the context as expected anyway.
			$content
		);
	}

	/**
	 * Detect the current order ID from various WooCommerce sources.
	 *
	 * @return int Order ID or 0 if not found.
	 */
	protected function detect_order_id(): int {

		if ( isset( $GLOBALS['order-received'] ) ) {
			return absint( $GLOBALS['order-received'] );
		}

		// Check the key in the URL.
		if ( function_exists( 'wc_get_order_id_by_order_key' ) && isset( $_GET['key'] ) ) {
			$order_id = wc_get_order_id_by_order_key( sanitize_text_field( $_GET['key'] ) );
			if ( $order_id > 0 ) {
				return $order_id;
			}
		}

		// Try to get from current WooCommerce page context
		global $wp_query;
		if ( isset( $wp_query->query_vars['order-received'] ) ) {
			$order_id = absint( $wp_query->query_vars['order-received'] );
			if ( $order_id > 0 ) {
				return $order_id;
			}
		}

		return 0;
	}


	/**
	 * Filter block context to add the order id.
	 *
	 * @hooked add_order_id_context
	 * @see render_block()
	 *
	 * @param array{postId:int,postType:string}                                                             $context
	 * @param array{blockName:string, attrs:array, innerBlocks:array, innerHTML:string, innerContent:array} $parsed_block
	 * @param \WP_Block|null                                                                                $parent_block
	 *
	 * @return array
	 */
	public function add_order_id_context( array $context, array $parsed_block, \WP_Block|null $parent_block ): array {

		$blockName = $parsed_block['blockName'];

		// No need to check plain HTML or ourself.
		if ( is_null( $blockName ) || 'bh-wp-bitcoin-gateway/bitcoin-order' === $blockName ) {
			return $context;
		}

		// Check if the block uses our context.
		$block_uses_context = WP_Block_Type_Registry::get_instance()->get_registered( $blockName )->get_uses_context();

		if ( ! in_array( 'bh-wp-bitcoin-gateway/orderId', $block_uses_context, true ) ) {
			return $context;
		}

		$context['bh-wp-bitcoin-gateway/orderId'] = $this->detect_order_id();

		return $context;
	}
}
