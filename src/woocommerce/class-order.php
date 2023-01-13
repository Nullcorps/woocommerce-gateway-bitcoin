<?php
/**
 * Constants for order meta keys.
 *
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce;

class Order {

	const BITCOIN_ADDRESS_META_KEY = 'bh_wc_bitcoin_gateway_address';

	const TRANSACTIONS_META_KEY = 'bh_wc_bitcoin_gateway_transactions';

	const EXCHANGE_RATE_AT_TIME_OF_PURCHASE_META_KEY = 'bh_wc_bitcoin_gateway_exchange_rate_at_time_of_purchase';

	const ORDER_TOTAL_BITCOIN_AT_TIME_OF_PURCHASE_META_KEY = 'bh_wc_bitcoin_gateway_bitcoin_total_at_time_of_purchase';

	const BITCOIN_AMOUNT_RECEIVED_META_KEY = 'bh_wc_bitcoin_gateway_bitcoin_amount_received';

	const LAST_CHECKED_META_KEY = 'bh_wc_bitcoin_gateway_last_checked_time';
}
