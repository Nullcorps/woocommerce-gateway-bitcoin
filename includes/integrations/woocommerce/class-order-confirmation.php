<?php
/**
 * The thank you page for blocks.
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\Integrations\WooCommerce;

use BrianHenryIE\WP_Bitcoin_Gateway\Integrations\WooCommerce\Modify_Template;

class Order_Confirmation {

	/**
	 * @hooked woocommerce_loaded
	 */
	public function init(): void {
		// Are we on the order confirmation page?

		/**
		 * This isn't working `global $wp;` is not set at this point.
		 */
		// if(!is_order_received_page()){
		// return;
		// }

		// TODO: Check we're logged in too... might be showing the "log in to see your order" message.

		$this->modify_template = new Modify_Template(
			'order-confirmation', // The slug of the template to modify.
			array( 'core/group', 'woocommerce/order-confirmation-summary' ),
			$this->get_block()
		);
	}

	protected function get_block(): array {

		return array(
			'blockName'    => null,
			'attrs'        => array(),
			'innerBlocks'  =>
				array(),
			'innerHTML'    => 'brian',
			'innerContent' =>
				array(
					0 => 'brian',
				),
		);
	}
}
