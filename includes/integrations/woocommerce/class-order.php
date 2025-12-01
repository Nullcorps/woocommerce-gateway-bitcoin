<?php
/**
 * Constants for order meta keys.
 *
 * @package    brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\Integrations\WooCommerce;

use ActionScheduler;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use BrianHenryIE\WP_Bitcoin_Gateway\Action_Scheduler\Background_Jobs;
use BrianHenryIE\WP_Bitcoin_Gateway\Action_Scheduler\Background_Jobs_Actions_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\Action_Scheduler\Background_Jobs_Scheduling_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address_Repository;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Wallet;
use BrianHenryIE\WP_Bitcoin_Gateway\Integrations\WooCommerce\Model\WC_Bitcoin_Order;
use BrianHenryIE\WP_Bitcoin_Gateway\API_Interface;
use Exception;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Order;

/**
 * Defines constants for metakeys.
 * Handles order status change events, to schedule/unschedule background tasks.
 */
class Order {
	use LoggerAwareTrait;

	const BITCOIN_ADDRESS_META_KEY = 'bh_wp_bitcoin_gateway_address';

	const EXCHANGE_RATE_AT_TIME_OF_PURCHASE_META_KEY = 'bh_wp_bitcoin_gateway_exchange_rate_at_time_of_purchase';

	const ORDER_TOTAL_BITCOIN_AT_TIME_OF_PURCHASE_META_KEY = 'bh_wp_bitcoin_gateway_bitcoin_total_at_time_of_purchase';

	const BITCOIN_AMOUNT_RECEIVED_META_KEY = 'bh_wp_bitcoin_gateway_bitcoin_amount_received';

	const LAST_CHECKED_META_KEY = 'bh_wp_bitcoin_gateway_last_checked_time';

	/**
	 * Constructor.
	 *
	 * @param API_Interface   $api The main plugin functions. Used to check is the gateway a Bitcoin gateway.
	 * @param LoggerInterface $logger A PSR logger.
	 */
	public function __construct(
		protected API_Interface $api,
		protected Background_Jobs_Scheduling_Interface $background_jobs,
		LoggerInterface $logger
	) {
		$this->setLogger( $logger );
	}

	/**
	 * TODO: hook.
	 *
	 * @hooked on API check payments do_action.
	 * NOT ~~@hooked Bitcoin_Address post type's status changed event~~
	 */
	public function on_bitcoin_address_post_type_post_status_change( \WP_Post $post, Bitcoin_Address $bitcoin_address ): void {

		$order_id = $post->post_parent;

		$wc_order = wc_get_order( $order_id );

		if ( ! ( $wc_order instanceof WC_Order ) ) {
			return;
		}

		// if($bitcoin_address->get_confirmed_balance())

		$wc_order->set_status( 'wc-processing' );
		$wc_order->maybe_set_date_paid();
	}

	/**
	 * TODO: this should be moved to post status change hook for bitcoin addresses post type
	 *
	 * When a new order is created, if it's a Bitcoin order, check the remaining number of unused addresses
	 *
	 * 20 is the standard number of addresses a wallet is expected to seek forward to monitor for payments.
	 *
	 * TODO: Confirm note "some implementations of the action pass the order object too.
	 *
	 * @hooked woocommerce_new_order
	 *
	 * @param int|numeric-string $order_id
	 * @param WC_Order           $order
	 *
	 * @throws Exception
	 * @see OrdersTableDataStore::update()
	 *
	 * @see WC_Order_Data_Store_CPT::create()
	 * @see WC_Order_Data_Store_CPT::update()
	 * @see OrdersTableDataStore::create()
	 */
	public function todo_check_addresses_after_new_orders( int|string $order_id, ?WC_Order $order = null ): void {
		if ( ! $this->api->is_order_has_bitcoin_gateway( $order_id ) ) {
			return;
		}

		/** @var WC_Order $order */
		$order = $order ?? wc_get_order( $order_id );

		$bitcoin_order = $this->api->get_order_details( $order );

		$wallet_id = $bitcoin_order->get_address()->get_wallet_parent_post_id();
		/** @var Bitcoin_Wallet $wallet */
		$wallet = new Bitcoin_Wallet( $wallet_id );

		$num_remaining_addresses = count( $wallet->get_fresh_addresses() );

		// Schedule address generation if needed.
		if ( $num_remaining_addresses < 20 ) {
			$this->logger->debug( "Under 20 addresses ($num_remaining_addresses) remaining, scheduling generate_new_addresses background job.", array( 'num_remaining_addresses' => $num_remaining_addresses ) );
			$this->background_jobs->schedule_check_newly_assigned_bitcoin_address_for_transactions();
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
	public function schedule_check_for_transactions( int $order_id, string $status_from, string $status_to ): void {

		if ( 'on-hold' !== $status_to ) {
			return;
		}

		if ( ! $this->api->is_order_has_bitcoin_gateway( $order_id ) ) {
			return;
		}

		// Schedule background check for payment.
		$hook = Background_Jobs_Actions_Interface::CHECK_ASSIGNED_ADDRESSES_TRANSACTIONS_HOOK;
		$args = array( 'order_id' => $order_id );

		if ( ! as_has_scheduled_action( $hook, $args ) ) {
			$timestamp         = time() + ( 10 * MINUTE_IN_SECONDS );
			$recurring_seconds = ( 10 * MINUTE_IN_SECONDS );
			$this->logger->debug( "New order created, `shop_order:{$order_id}`, scheduling background job to check for payments" );
			as_schedule_recurring_action( $timestamp, $recurring_seconds, $hook, $args );
		}
	}

	/**
	 * When an order's status changes away from "pending" and "on-hold", cancel the scheduled background.
	 *
	 * E.g. when the order is paid or canceled.
	 *
	 * @hooked woocommerce_order_status_changed
	 * @see WC_Order::status_transition()
	 *
	 * @param int|numeric-string $order_id The id of the order whose status has changed.
	 * @param string             $status_from The old status.
	 * @param string             $status_to The new status.
	 */
	public function unschedule_check_for_transactions( int|string $order_id, string $status_from, string $status_to ): void {

		// TODO: failed, custom statuses
		if ( in_array( $status_to, array( 'pending', 'on-hold' ), true ) ) {
			return;
		}

		if ( ! $this->api->is_order_has_bitcoin_gateway( $order_id ) ) {
			return;
		}

		if ( empty( $status_to ) ) {
			$status_to = 'trash?';
		}

		$context = array(
			'order_id'    => $order_id,
			'status_from' => $status_from,
			'status_to'   => $status_to,
		);

		$hook  = Background_Jobs_Actions_Interface::CHECK_ASSIGNED_ADDRESSES_TRANSACTIONS_HOOK;
		$args  = array( 'order_id' => $order_id );
		$query = array(
			'hook' => $hook,
			'args' => $args,
		);

		$context['action_scheduler_query'] = $query;

		$actions = as_get_scheduled_actions( $query );
		if ( ! empty( $actions ) ) {
			$action_id = array_key_first( $actions );
			$this->logger->debug( "`shop_order:{$order_id}` status changed from $status_from to $status_to, running `as_unschedule_all_actions` for check_unpaid_order job, action_id $action_id.", $context );
			as_unschedule_all_actions( $hook, $args );
		} else {
			$this->logger->debug( "`shop_order:{$order_id}` status changed from $status_from to $status_to. No check_unpaid_order background job present to cancel.", $context );
		}
	}
}
