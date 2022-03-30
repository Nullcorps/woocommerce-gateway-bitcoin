<?php
/**
 * Constants for order meta keys.
 *
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

namespace Nullcorps\WC_Gateway_Bitcoin\WooCommerce;

class Order {

	const BITCOIN_ADDRESS_META_KEY = 'woobtc_address';

	const TRANSACTIONS_META_KEY = 'woobtc_transactions';

	const EXCHANGE_RATE_AT_TIME_OF_PURCHASE_META_KEY = 'woobtc_exchange_rate_at_time_of_purchase';

	const ORDER_TOTAL_BITCOIN_AT_TIME_OF_PURCHASE_META_KEY = 'woobtc_bitcoin_total_at_time_of_purchase';

	const BITCOIN_AMOUNT_RECEIVED_META_KEY = 'woobtc_bitcoin_amount_received';

	const LAST_CHECKED_META_KEY = 'woobtc_last_checked_time';
}
