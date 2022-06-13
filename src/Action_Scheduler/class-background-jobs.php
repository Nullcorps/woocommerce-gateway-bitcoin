<?php
/**
 * After five minutes check the unpaid order for payments.
 *  - TODO After x unpaid time, mark unpaid orders as failed/cancelled.
 * When the fresh address list falls below the cache threshold, generate new addresses.
 *
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

namespace Nullcorps\WC_Gateway_Bitcoin\Action_Scheduler;

use Exception;
use Nullcorps\WC_Gateway_Bitcoin\API\API_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Order;

/**
 * Handles do_action initiated from Action Scheduler.
 */
class Background_Jobs {
	use LoggerAwareTrait;

	const CHECK_UNPAID_ORDER_HOOK               = 'nullcorps_bitcoin_check_unpaid_order';
	const GENERATE_NEW_ADDRESSES_HOOK           = 'nullcorps_bitcoin_generate_new_addresses';
	const CHECK_NEW_ADDRESSES_TRANSACTIONS_HOOK = 'nullcorps_bitcoin_check_new_addresses_transactions';

	/**
	 * Main class for carrying out the jobs.
	 *
	 * @var API_Interface
	 */
	protected API_Interface $api;

	/**
	 * Constructor
	 *
	 * @param API_Interface   $api Main plugin class.
	 * @param LoggerInterface $logger PSR logger.
	 */
	public function __construct( API_Interface $api, LoggerInterface $logger ) {
		$this->setLogger( $logger );
		$this->api = $api;
	}

	/**
	 * Query a Blockchain API for updates to the order. If the order is still awaiting payment, schedule another job
	 * to check again soon.
	 *
	 * @hooked nullcorps_bitcoin_check_unpaid_order
	 * @see self::CHECK_UNPAID_ORDER_HOOK
	 *
	 * @param int $order_id WooCommerce order id to check.
	 */
	public function check_unpaid_order( int $order_id ): void {

		$this->logger->debug( 'Starting check_unpaid_order() background job for ' . $order_id );

		$order = wc_get_order( $order_id );

		if ( ! ( $order instanceof WC_Order ) ) {
			$this->logger->error( 'Invalid order id passed to check_unpaid_order() background job', array( 'order_id' => $order_id ) );
			return;
		}

		try {
			$result = $this->api->get_order_details( $order );
		} catch ( Exception $exception ) {

			// 403.
			// TODO: Log better.
			$this->logger->error( 'Error getting order details for order ' . $order_id, array( 'order_id' => $order_id ) );
		} finally {

			/**
			 * We've already verified in this function that $order_id is for a valid WC_Order object.
			 *
			 * @var WC_Order $order
			 */
			$order = wc_get_order( $order_id );

			if ( ! in_array( $order->get_status(), array( 'pending', 'on-hold' ), true ) ) {
				return;
			}

			// While there are still unpaid Bitcoin orders, keep checking for payments.
			$hook = self::CHECK_UNPAID_ORDER_HOOK;
			$args = array( 'order_id' => $order_id );
			if ( ! as_has_scheduled_action( $hook, $args ) ) {
				$timestamp = time() + ( 5 * MINUTE_IN_SECONDS );
				$this->logger->debug(
					"{$order_id} still unpaid, scheduling new check_unpaid_order() background job.",
					array(
						'timestamp' => $timestamp,
						'hook'      => $hook,
						'args'      => $args,
					)
				);
				as_schedule_single_action( $timestamp, $hook, $args );
			}
		}

	}


	/**
	 * When available addresses fall below a threshold, more are generated on a background job.
	 *
	 * @hooked nullcorps_bitcoin_generate_new_addresses
	 * @see self::GENERATE_NEW_ADDRESSES_HOOK
	 */
	public function generate_new_addresses(): void {

		$this->logger->debug( 'Starting generate_new_addresses() background job.' );

		$result = $this->api->generate_new_addresses();
	}


	/**
	 * After new addresses have been created, we check to see are they fresh/available to use.
	 * It's not unlikely we'll hit 429 rate limits during this, so we'll loop through as many as we can,
	 * then schedule a new job when we're told to stop.
	 *
	 * @hooked nullcorps_bitcoin_check_new_addresses_transactions
	 * @see self::CHECK_NEW_ADDRESSES_TRANSACTIONS_HOOK
	 */
	public function check_new_addresses_for_transactions(): void {

		$this->logger->debug( 'Starting check_new_addresses_for_transactions() background job.' );

		$this->api->check_new_addresses_for_transactions();
	}

}
