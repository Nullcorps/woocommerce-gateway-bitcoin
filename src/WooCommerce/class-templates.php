<?php
/**
 * Register the `bitcoin-paid.php` and `bitcoin-unpaid.php` templates so they can be found with `wc_get_template()`.
 *
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

namespace Nullcorps\WC_Gateway_Bitcoin\WooCommerce;

class Templates {

	const BITCOIN_UNPAID_TEMPLATE_NAME = 'bitcoin-unpaid.php';
	const BITCOIN_PAID_TEMPLATE_NAME   = 'bitcoin-paid.php';

	/**
	 *
	 * `wc_get_template( 'bitcoin-paid.php', $formatted_order_details_array );`.
	 *
	 * @see woocommerce_locate_template
	 * @see https://wphave.com/include-woocommerce-templates-from-plugin/
	 *
	 * @hooked wc_get_template
	 *
	 * @param string       $template
	 * @param string       $template_name
	 * @param array<mixed> $args Array of values to be exploded and made available to the included template.
	 * @param string       $template_path
	 * @param string       $default_path
	 *
	 * @return string
	 */
	public function load_bitcoin_templates( string $template, string $template_name, array $args, string $template_path, string $default_path ): string {

		$templates = array(
			self::BITCOIN_UNPAID_TEMPLATE_NAME,
			self::BITCOIN_PAID_TEMPLATE_NAME,
			Email::TEMPLATE_NAME,
			My_Account_View_Order::TEMPLATE_NAME,
			Thank_You::TEMPLATE_NAME,
		);

		// Unrelated to us, leave early.
		if ( ! in_array( $template_name, $templates, true ) ) {
			return $template;
		}

		// It will default to a string suggesting the template exists under the WooCommerce plugin directory, which will not exist.
		// Or could be already set by an earlier filter, where it probably will exist.
		if ( ! empty( $template ) && file_exists( $template ) ) {
			return $template;
		}

		// Check does it exist inside the theme.
		$theme_template = locate_template( array( '/woocommerce/' . $template_name, $template_name ) );

		if ( $theme_template ) {
			return $theme_template;
		}

		return NULLCORPS_WOOCOMMERCE_GATEWAY_BITCOIN_PATH . '/templates/' . $template_name;

	}

}
