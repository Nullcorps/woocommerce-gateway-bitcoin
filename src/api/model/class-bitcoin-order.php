<?php
/**
 * Really just a wrapper for WC_Order.
 *
 * I.e. to return its string meta address as a typed Bitcoin_Address etc.
 *
 * @package brianehnryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\API\Model;

use BadMethodCallException;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address_Factory;
use BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce\Bitcoin_Gateway;
use BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce\Order;
use DateTimeInterface;
use WC_Order;
use WC_Payment_Gateways;

/**
 *
 * @method get_id()
 * @method get_status()
 * @method get_date_created()
 * @method add_order_note()
 * @method payment_complete()
 * @method is_paid()
 * @method save()
 * @method get_currency()
 * @method get_date_paid()
 */
class Bitcoin_Order implements Bitcoin_Order_Interface {

	protected WC_Order $wc_order;

	protected Bitcoin_Address $address;

	protected ?Bitcoin_Gateway $gateway;

	/**
	 * The number of confirmations the order needs for transactions.
	 */
	protected int $confirmations;
	protected $amount_received;
	protected DateTimeInterface $last_checked_time;

	/**
	 * @param string       $name
	 * @param array<mixed> $arguments
	 *
	 * @return mixed
	 */
	public function __call( string $name, array $arguments ): mixed {
		if ( is_callable( array( $this->wc_order, $name ) ) ) {
			return call_user_func_array( array( $this->wc_order, $name ), $arguments );
		}
		throw new BadMethodCallException();
	}

	public function __construct( WC_Order $wc_order, Bitcoin_Address_Factory $bitcoin_address_factory ) {

		$this->wc_order = $wc_order;

		try {
			$bitcoin_address         = $wc_order->get_meta( Order::BITCOIN_ADDRESS_META_KEY );
			$bitcoin_address_post_id = $bitcoin_address_factory->get_post_id_for_address( $bitcoin_address );
			$this->address           = $bitcoin_address_factory->get_by_post_id( $bitcoin_address_post_id );
		} catch ( \Exception $exception ) {
			// $this->logger->warning( "`shop_order:{$order->get_id()}` has no Bitcoin address.", array( 'order_id' => $order->get_id() ) );
			throw new \Exception( 'Problem with order Bitcoin address.' );
		}
	}

	/**
	 * The order price in Bitcoin at the time of purchase.
	 */
	public function get_btc_total_price(): int {
		return floatval( $this->wc_order->get_meta( Order::ORDER_TOTAL_BITCOIN_AT_TIME_OF_PURCHASE_META_KEY ) );
	}

	/**
	 * The Bitcoin exchange rate at the time of purchase.
	 */
	public function get_btc_exchange_rate(): float {
		return floatval( $this->wc_order->get_meta( Order::EXCHANGE_RATE_AT_TIME_OF_PURCHASE_META_KEY ) );
	}

	public function get_address(): Bitcoin_Address {
		return $this->address;
	}

	public function get_last_checked_time(): ?DateTimeInterface {
		$last_checked_time = $this->wc_order->get_meta( Order::LAST_CHECKED_META_KEY );
		$last_checked_time = empty( $last_checked_time ) ? null : $last_checked_time;
		return $last_checked_time;
	}

	public function set_last_checked_time( DateTimeInterface $last_checked_time ): void {
		// @phpstan-ignore-next-line This works fine.
		$this->wc_order->add_meta_data( Order::LAST_CHECKED_META_KEY, $last_checked_time, true );
		$this->last_checked_time = $last_checked_time;
	}

	/**
	 * Get the order's gateway.
	 *
	 * Since the gateway id could change, particularly where there are multiple instances, it may happen that the id
	 * in the order does not match an existing gateway, => return null.
	 */
	public function get_gateway(): ?Bitcoin_Gateway {
		return WC_Payment_Gateways::instance()->payment_gateways[ $this->wc_order->get_payment_method() ] ?? null;
	}

	/**
	 * Get the total value with the required number of confirmations at the last checked time.
	 */
	public function get_amount_received() {
		return $this->amount_received;
	}

	/**
	 * @param $updated_confirmed_value
	 */
	public function set_amount_received( $updated_confirmed_value ): void {
		$this->wc_order->add_meta_data( Order::BITCOIN_AMOUNT_RECEIVED_META_KEY, $updated_confirmed_value, true );
		$this->amount_received = $updated_confirmed_value;
	}
}
