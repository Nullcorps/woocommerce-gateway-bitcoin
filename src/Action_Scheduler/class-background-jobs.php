<?php
/**
 * After five minutes check the unpaid order for payments.
 *  - TODO After x unpaid time, mark unpaid orders as failed/cancelled.
 * When the fresh address list falls below the cache threshold, generate new addresses.
 *
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

namespace Nullcorps\WC_Gateway_Bitcoin\Action_Scheduler;

use Nullcorps\WC_Gateway_Bitcoin\API\API_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Order;

class Background_Jobs {
	use LoggerAwareTrait;

	const CHECK_UNPAID_ORDER_HOOK     = 'nullcorps_bitcoin_check_unpaid_order';
	const GENERATE_NEW_ADDRESSES_HOOK = 'nullcorps_bitcoin_generate_new_addresses';

	protected API_Interface $api;

	public function __construct( API_Interface $api, LoggerInterface $logger ) {
		$this->setLogger( $logger );
		$this->api = $api;
	}

	/**
	 *
	 * @param int $order_id
	 */
	public function check_unpaid_order( $order_id ): void {

		$this->logger->debug( 'Background job starting check_unpaid_order() for ' . $order_id );

		$order = wc_get_order( $order_id );

		if ( ! ( $order instanceof WC_Order ) ) {
			$this->logger->error( 'Invalid order id passed to check_unpaid_order() background job', array( 'order_id' => $order_id ) );
			return;
		}

		try {
			$result = $this->api->get_order_details( $order );
		} catch ( \Exception $exception ) {

			// 403.
			// TODO: Log better.
			$this->logger->error( 'Error getting order details for order ' . $order_id, array( 'order_id' => $order_id ) );
		} finally {

			/** @var WC_Order $order */
			$order = wc_get_order( $order_id );

			if ( ! in_array( $order->get_status(), array( 'pending', 'on-hold' ), true ) ) {
				return;
			}

			// While there are still unpaid Bitcoin orders, keep checking for payments.
			$hook = self::CHECK_UNPAID_ORDER_HOOK;
			$args = array( 'order_id' => $order_id );
			if ( ! as_has_scheduled_action( $hook, $args ) ) {
				$timestamp = time() + ( 5 * MINUTE_IN_SECONDS );
				as_schedule_single_action( $timestamp, $hook, $args );
			}
		}

	}

	/**
	 * When available addresses fall below a threshold, more are generated on a background job.
	 *
	 * @return void
	 */
	public function generate_new_addresses(): void {

		$this->logger->debug( 'Background job starting generate_new_addresses()' );

		$this->api->generate_new_addresses();

	}

}
