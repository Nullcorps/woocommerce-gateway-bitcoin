<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\Frontend;

use BrianHenryIE\WP_Bitcoin_Gateway\API_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\Settings_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Order;

/**
 * Enqueue CSS, JS and JSON order details on the order-received page.
 */
class Frontend_Assets {
	use LoggerAwareTrait;

	/**
	 * Get the plugin version for caching.
	 */
	protected Settings_Interface $settings;

	/**
	 * Check is the order a Bitcoin order.
	 * Get the order details.
	 */
	protected API_Interface $api;

	/**
	 * Constructor
	 *
	 * @param API_Interface      $api The main plugin functions.
	 * @param Settings_Interface $settings The plugin settings.
	 * @param LoggerInterface    $logger A PSR logger.
	 */
	public function __construct( API_Interface $api, Settings_Interface $settings, LoggerInterface $logger ) {
		$this->setLogger( $logger );
		$this->settings = $settings;
		$this->api      = $api;
	}

	/**
	 * Register the stylesheets for the frontend-facing side of the site.
	 *
	 * @hooked wp_enqueue_scripts
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles(): void {

		$order_id = isset( $GLOBALS['order-received'] ) ? $GLOBALS['order-received'] : ( isset( $GLOBALS['view-order'] ) ? $GLOBALS['view-order'] : 0 );
		$order_id = intval( $order_id );

		if ( empty( $order_id ) || ! $this->api->is_order_has_bitcoin_gateway( $order_id ) ) {
			return;
		}

		$version = $this->settings->get_plugin_version();
		wp_enqueue_style( 'bh-wp-bitcoin-gateway', $this->settings->get_plugin_url() . 'assets/css/bh-wp-bitcoin-gateway.css', array(), $version, 'all' );

		wp_enqueue_style( 'dashicons' );
	}

	/**
	 * Register the JavaScript for the frontend-facing side of the site.
	 *
	 * @hooked wp_enqueue_scripts
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts(): void {

		$order_id = isset( $GLOBALS['order-received'] ) ? $GLOBALS['order-received'] : ( isset( $GLOBALS['view-order'] ) ? $GLOBALS['view-order'] : 0 );
		$order_id = intval( $order_id );

		if ( empty( $order_id ) || ! $this->api->is_order_has_bitcoin_gateway( $order_id ) ) {
			return;
		}

		/**
		 * We confirmed this is a shop_order in the previous line.
		 *
		 * @var WC_Order $order
		 */
		$order = wc_get_order( $order_id );

		try {
			$order_details = $this->api->get_order_details( $order );
		} catch ( \Exception $exception ) {
			$this->logger->error( 'Failed to get order details when enqueuing scripts: ' . $exception->getMessage(), array( 'exception' => $exception ) );
			return;
		}

		$version = $this->settings->get_plugin_version();

		$script_url = $this->settings->get_plugin_url() . 'assets/js/frontend/bh-wp-bitcoin-gateway.min.js';

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$script_url = str_replace( '.min', '', $script_url );
		}

		wp_enqueue_script( 'bh-wp-bitcoin-gateway', $script_url, array( 'jquery' ), $version, true );

		$order_details_json = wp_json_encode( $order_details, JSON_PRETTY_PRINT );

		$ajax_data      = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( self::class ),
		);
		$ajax_data_json = wp_json_encode( $ajax_data, JSON_PRETTY_PRINT );

		$script = <<<EOD
var bh_wp_bitcoin_gateway_ajax_data = $ajax_data_json;
var bh_wp_bitcoin_gateway_order_details = $order_details_json;
EOD;

		wp_add_inline_script(
			'bh-wp-bitcoin-gateway',
			$script,
			'before'
		);

	}

}
