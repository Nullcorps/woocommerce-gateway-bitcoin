<?php
/**
 * After five minutes check the unpaid order for payments.
 *  - TODO After x unpaid time, mark unpaid orders as failed/cancelled.
 * When the fresh address list falls below the cache threshold, generate new addresses.
 *
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway\Action_Scheduler;

use ActionScheduler;
use Exception;
use BrianHenryIE\WC_Bitcoin_Gateway\API_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Order;

/**
 * Handles do_action initiated from Action Scheduler.
 */
class Background_Jobs {
	use LoggerAwareTrait;

	const CHECK_UNPAID_ORDER_HOOK               = 'bh_wc_bitcoin_gateway_check_unpaid_order';
	const GENERATE_NEW_ADDRESSES_HOOK           = 'bh_wc_bitcoin_gateway_generate_new_addresses';
	const CHECK_NEW_ADDRESSES_TRANSACTIONS_HOOK = 'bh_wc_bitcoin_gateway_check_new_addresses_transactions';

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
	 * @hooked bh_wc_bitcoin_gateway_check_unpaid_order
	 * @see self::CHECK_UNPAID_ORDER_HOOK
	 *
	 * @param int $order_id WooCommerce order id to check.
	 */
	public function check_unpaid_order( int $order_id ): void {

		// How to find the action_id of the action currently being run?
		$query = array(
			'hook' => self::CHECK_UNPAID_ORDER_HOOK,
			'args' => array( 'order_id' => $order_id ),
		);

		$action_id = ActionScheduler::store()->query_action( $query );
		$claim_id  = ActionScheduler::store()->get_claim_id( $action_id );

		$this->logger->debug(
			"Running check_unpaid_order background task for `shop_order:{$order_id}` action id: {$action_id}, claim id: {$claim_id}",
			array(
				'order_id'  => $order_id,
				'task'      => $query,
				'action_id' => $action_id,
				'claim_id'  => $claim_id,
			)
		);

		$order = wc_get_order( $order_id );

		if ( ! ( $order instanceof WC_Order ) ) {
			$this->logger->error( 'Invalid order id ' . $order_id . ' passed to check_unpaid_order() background job', array( 'order_id' => $order_id ) );
			return;
		}

		try {
			$result = $this->api->get_order_details( $order );
		} catch ( Exception $exception ) {

			// 403.
			// TODO: Log better.
			$this->logger->error( 'Error getting details for `shop_order:' . $order_id . '`', array( 'order_id' => $order_id ) );
		}
	}


	/**
	 * When available addresses fall below a threshold, more are generated on a background job.
	 *
	 * @hooked bh_wc_bitcoin_gateway_generate_new_addresses
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
	 * @hooked bh_wc_bitcoin_gateway_check_new_addresses_transactions
	 * @see self::CHECK_NEW_ADDRESSES_TRANSACTIONS_HOOK
	 */
	public function check_new_addresses_for_transactions(): void {

		$this->logger->debug( 'Starting check_new_addresses_for_transactions() background job.' );

		$this->api->check_new_addresses_for_transactions();
	}

}
