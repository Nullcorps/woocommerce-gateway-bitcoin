<?php
/**
 * Constants for order meta keys.
 *
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce;

use BrianHenryIE\WC_Bitcoin_Gateway\Action_Scheduler\Background_Jobs;
use BrianHenryIE\WC_Bitcoin_Gateway\API_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Defines constants for metakeys.
 * Handles order status change events, to schedule/unschedule background tasks.
 */
class Order {
	use LoggerAwareTrait;

	/**
	 * Used to check is the gateway a Bitcoin gateway.
	 */
	protected API_Interface $api;

	const BITCOIN_ADDRESS_META_KEY = 'bh_wc_bitcoin_gateway_address';

	const TRANSACTIONS_META_KEY = 'bh_wc_bitcoin_gateway_transactions';

	const EXCHANGE_RATE_AT_TIME_OF_PURCHASE_META_KEY = 'bh_wc_bitcoin_gateway_exchange_rate_at_time_of_purchase';

	const ORDER_TOTAL_BITCOIN_AT_TIME_OF_PURCHASE_META_KEY = 'bh_wc_bitcoin_gateway_bitcoin_total_at_time_of_purchase';

	const BITCOIN_AMOUNT_RECEIVED_META_KEY = 'bh_wc_bitcoin_gateway_bitcoin_amount_received';

	const LAST_CHECKED_META_KEY = 'bh_wc_bitcoin_gateway_last_checked_time';

	/**
	 * Constructor.
	 *
	 * @param API_Interface   $api The main plugin functions.
	 * @param LoggerInterface $logger A PSR logger.
	 */
	public function __construct( API_Interface $api, LoggerInterface $logger ) {
		$this->setLogger( $logger );
		$this->api = $api;
	}

	/**
	 * When an order's status is set to "on-hold", schedule a background job to check for payments.
	 *
	 * @hooked woocommerce_order_status_changed
	 * @see WC_Order::status_transition()
	 *
	 * @param int    $order_id The id of the order whose status has changed.
	 * @param string $status_from The old status.
	 * @param string $status_to The new status.
	 */
	public function schedule_check_for_transactions( int $order_id, string $status_from, string $status_to ): void {

		if ( 'on-hold' !== $status_to ) {
			return;
		}

		if ( ! $this->api->is_order_has_bitcoin_gateway( $order_id ) ) {
			return;
		}

		// Schedule background check for payment.
		$hook = Background_Jobs::CHECK_UNPAID_ORDER_HOOK;
		$args = array( 'order_id' => $order_id );

		if ( ! as_has_scheduled_action( $hook, $args ) ) {
			$timestamp         = time() + ( 5 * MINUTE_IN_SECONDS );
			$recurring_seconds = ( 5 * MINUTE_IN_SECONDS );
			$this->logger->debug( "New order created, `shop_order:{$order_id}`, scheduling background job to check for payments" );
			as_schedule_recurring_action( $timestamp, $recurring_seconds, $hook, $args );
		}
	}

	/**
	 * When an order's status is set to "on-hold", schedule a background job to check for payments.
	 *
	 * @hooked woocommerce_order_status_changed
	 * @see WC_Order::status_transition()
	 *
	 * @param int    $order_id The id of the order whose status has changed.
	 * @param string $status_from The old status.
	 * @param string $status_to The new status.
	 */
	public function unschedule_check_for_transactions( int $order_id, string $status_from, string $status_to ): void {

		if ( ! in_array( $status_to, wc_get_is_paid_statuses(), true ) ) {
			return;
		}

		if ( ! $this->api->is_order_has_bitcoin_gateway( $order_id ) ) {
			return;
		}

		$hook = Background_Jobs::CHECK_UNPAID_ORDER_HOOK;
		$args = array( 'order_id' => $order_id );

		as_unschedule_action( $hook, $args );
	}
}
