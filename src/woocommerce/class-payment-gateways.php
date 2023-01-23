<?php
/**
 * Add the payment gateway to WooCommerce's list of gateways.
 *
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use BrianHenryIE\WC_Bitcoin_Gateway\API_Interface;
use BrianHenryIE\WC_Bitcoin_Gateway\Settings_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Payment_Gateway;
use WC_Payment_Gateways;

/**
 * Add the payment gateway's class name to WooCommerce's list of gateways it will
 * later instantiate.
 */
class Payment_Gateways {
	use LoggerAwareTrait;

	protected API_Interface $api;

	protected Settings_Interface $settings;

	public function __construct( API_Interface $api, Settings_Interface $settings, LoggerInterface $logger ) {
		$this->setLogger( $logger );
		$this->settings = $settings;
		$this->api      = $api;
	}

	/**
	 * Add the Gateway to WooCommerce.
	 *
	 * @hooked woocommerce_payment_gateways
	 *
	 * @param string[] $gateways The payment gateways registered with WooCommerce.
	 *
	 * @return string[]
	 * @see WC_Payment_Gateways::init()
	 */
	public function add_to_woocommerce( array $gateways ): array {

		if ( ! $this->api->is_server_has_dependencies() ) {
			return $gateways;
		}

		$gateways[] = Bitcoin_Gateway::class;

		return $gateways;
	}

	/**
	 * Registers the gateway with WooCommerce Blocks checkout integration.
	 *
	 * It seems the `woocommerce_payment_gateways` filter is not run before this.
	 *
	 * @hooked woocommerce_blocks_payment_method_type_registration
	 *
	 * @param PaymentMethodRegistry $payment_method_registry WooCommerce class which handles blocks checkout gateways.
	 */
	public function register_woocommerce_block_checkout_support( PaymentMethodRegistry $payment_method_registry ): void {

		foreach ( $this->api->get_bitcoin_gateways() as $gateway ) {

			$support = new Bitcoin_Gateway_Blocks_Checkout_Support( $gateway, $this->settings );
			$payment_method_registry->register( $support );
		}
	}

	/**
	 * Set the PSR logger on each gateway instance.
	 *
	 * @hooked woocommerce_available_payment_gateways
	 *
	 * @param WC_Payment_Gateway[] $available_gateways The gateways to be displayed on the checkout.
	 *
	 * @return WC_Payment_Gateway[]
	 */
	public function add_logger_to_gateways( array $available_gateways ): array {

		foreach ( $available_gateways as $gateway ) {
			if ( $gateway instanceof Bitcoin_Gateway ) {
				$gateway->setLogger( $this->logger );
			}
		}

		return $available_gateways;
	}
}
