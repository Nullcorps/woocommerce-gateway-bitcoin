<?php
/**
 * Add the payment gateway to WooCommerce's list of gateways.
 *
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

namespace Nullcorps\WC_Gateway_Bitcoin\WooCommerce;

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

	public function __construct( LoggerInterface $logger ) {
		$this->setLogger( $logger );
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

		$gateways[] = WC_Gateway_Bitcoin::class;

		return $gateways;
	}

	/**
	 * When linking to WooCommerce/Settings/Payments from plugins.php, filter to only instances of this gateway.
	 *
	 * The plugins.php code checks for multiple instances of the gateway, then uses the `class=nullcorps-wc-gateway-bitcoin`
	 * parameter on the Settings link to invoke this function.
	 *
	 * i.e. `wp-admin/admin.php?page=wc-settings&tab=checkout&class=nullcorps-wc-gateway-bitcoin`.
	 *
	 * TODO: Is this hook right?
	 *
	 * @hooked woocommerce_payment_gateways
	 *
	 * @param array<string|WC_Payment_Gateway> $gateways WC_Payment_Gateway subclass instance or class names of payment gateways registered with WooCommerce.
	 *
	 * @return array<string|WC_Payment_Gateway>
	 * @see WC_Payment_Gateways::init()
	 */
	public function filter_to_only_bitcoin_gateways( array $gateways ): array {

		global $current_tab;

		if ( 'checkout' !== $current_tab || ! isset( $_GET['class'] ) || 'nullcorps-wc-gateway-bitcoin' !== wp_unslash( $_GET['class'] ) ) {
			return $gateways;
		}

		$bitcoin_gateways = array();
		foreach ( $gateways as $gateway ) {

			// Only handling one level of superclass. TODO: `class_parents()`.
			if ( WC_Gateway_Bitcoin::class === $gateway
				|| ( $gateway instanceof WC_Gateway_Bitcoin )
				|| ( is_string( $gateway ) && class_exists( $gateway ) && get_parent_class( $gateway ) === WC_Gateway_Bitcoin::class ) ) {
				$bitcoin_gateways[] = $gateway;
			}
		}

		return $bitcoin_gateways;
	}

	/**
	 * Set the PSR logger on each gateway instance.
	 *
	 * @hooked woocommerce_available_payment_gateways
	 *
	 * @param array<string, WC_Payment_Gateway> $available_gateways The gateways to be displayed on the checkout.
	 *
	 * @return array<string, WC_Payment_Gateway>
	 */
	public function add_logger_to_gateways( array $available_gateways ): array {

		foreach ( $available_gateways as $gateway ) {
			if ( $gateway instanceof WC_Gateway_Bitcoin ) {
				$gateway->setLogger( $this->logger );
			}
		}

		return $available_gateways;
	}
}
