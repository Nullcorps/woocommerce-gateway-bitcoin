<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway\Frontend;

use BrianHenryIE\WC_Bitcoin_Gateway\API_Interface;
use BrianHenryIE\WC_Bitcoin_Gateway\Settings_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 *
 */
class Frontend_Assets {
	use LoggerAwareTrait;

	protected Settings_Interface $settings;

	protected API_Interface $api;

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

		wp_enqueue_style( 'bh-wc-bitcoin-gateway', $this->settings->get_plugin_url() . 'assets/css/bh-wc-bitcoin-gateway.css', array(), $version, 'all' );

		wp_enqueue_style( 'dashicons' );
	}

	/**
	 * Register the JavaScript for the frontend-facing side of the site.
	 *
	 * @hooked ?wp_enqueue_scripts
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts(): void {

		$order_id = isset( $GLOBALS['order-received'] ) ? $GLOBALS['order-received'] : ( isset( $GLOBALS['view-order'] ) ? $GLOBALS['view-order'] : 0 );
		$order_id = intval( $order_id );

		if ( empty( $order_id ) || ! $this->api->is_order_has_bitcoin_gateway( $order_id ) ) {
			return;
		}

		/** @var \WC_Order $order */
		$order = wc_get_order( $order_id );

		try {
			$order_details = $this->api->get_order_details( $order );
		} catch ( \Exception $exception ) {
			$this->logger->error( 'Failed to get order details when enqueuing scripts: ' . $exception->getMessage(), array( 'exception' => $exception ) );
			return;
		}

		$version = $this->settings->get_plugin_version();

		wp_enqueue_script( 'bh-wc-bitcoin-gateway', $this->settings->get_plugin_url() . 'assets/js/bh-wc-bitcoin-gateway.js', array( 'jquery' ), $version, true );

		$order_details_json = wp_json_encode( $order_details, JSON_PRETTY_PRINT );

		$ajax_data      = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( self::class ),
		);
		$ajax_data_json = wp_json_encode( $ajax_data, JSON_PRETTY_PRINT );

		$script = <<<EOD
var bh_wc_bitcoin_gateway_ajax_data = $ajax_data_json;
var bh_wc_bitcoin_gateway_order_details = $order_details_json;
EOD;

		wp_add_inline_script(
			'bh-wc-bitcoin-gateway',
			$script,
			'before'
		);

	}

}
