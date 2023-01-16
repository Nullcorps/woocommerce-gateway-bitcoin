<?php
/**
 * Print an admin notice if the required GMP PHP extension is not present.
 *
 * @package brianhenryie/bh-wc-bitcoin-gateway
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway\Admin;

use BrianHenryIE\WC_Bitcoin_Gateway\API_Interface;
use BrianHenryIE\WC_Bitcoin_Gateway\Settings_Interface;

/**
 * Hooks into wp-admin's admin_notice action and prints a warning if the plugin's dependencies are not present.
 *
 * @see wp-admin/admin-header.php
 */
class Dependencies_Notice {

	/**
	 * Used to get the plugin basename for generating the deactivate link.
	 *
	 * @var Settings_Interface
	 */
	protected Settings_Interface $settings;

	/**
	 * Used to find is the dependency present or not.
	 *
	 * @var API_Interface
	 */
	protected API_Interface $api;

	/**
	 * Constructor
	 *
	 * @param API_Interface      $api The main plugin functions.
	 * @param Settings_Interface $settings The plugin settings.
	 */
	public function __construct( API_Interface $api, Settings_Interface $settings ) {
		$this->api      = $api;
		$this->settings = $settings;
	}

	/**
	 * Print the admin notice, if the dependency is missing and it is an admin logged in.
	 * The notice links to the PHP.net GMP page, and contains a deactivate link for the plugin.
	 *
	 * @hooked admin_notices
	 */
	public function print_dependencies_notice(): void {

		if ( $this->api->is_server_has_dependencies() ) {
			return;
		}

		if ( ! current_user_can( 'administrator' ) ) {
			return;
		}

		echo '<div class="notice notice-warning is-dismissible">';
		echo '<p>';
		echo '<b>Bitcoin Gateway</b>.';
		echo ' ';

		$gmp_link = '<a target="_blank" href="https://www.php.net/manual/en/book.gmp.php">GMP (GNU Multiple Precision Arithmetic Library)</a>';

		printf(
			/* translators: %s is replaced with a link to the PHP.net page for the missing GMP extension. */
			esc_html( __( 'Required PHP extension %s is not installed on this server. This is required for the calculations to derive Bitcoin payment addresses.', 'bh-wc-bitcoin-gateway' ) ),
			wp_kses(
				$gmp_link,
				array(
					'a' => array(
						'target' => array(),
						'href'   => array(),
					),
				)
			)
		);
		echo ' ';

		echo wp_kses( __( 'Please <b>contact your hosting provider for support</b>.', 'bh-wc-bitcoin-gateway' ), array( 'b' => array() ) );
		echo ' ';

		$deactivate_link = wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'deactivate',
					'plugin' => $this->settings->get_plugin_basename(),
				),
				admin_url(
					'plugins.php'
				)
			),
			"deactivate-plugin_{$this->settings->get_plugin_basename()}"
		);

		echo ' <a href="' . esc_url( $deactivate_link ) . '">';
		echo esc_html( __( 'Deactivate Bitcoin Gateway plugin', 'bh-wc-bitcoin-gateway' ) );
		echo '</a>.';

		echo '</p>';
		echo '</div>';
	}
}
