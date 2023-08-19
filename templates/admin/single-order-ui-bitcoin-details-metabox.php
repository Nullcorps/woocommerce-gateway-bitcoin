<?php
/**
 * Friendly info table to display on the admin order ui.
 *
 * @see \BrianHenryIE\WP_Bitcoin_Gateway\API_Interface::get_order_details()
 *
 * @var array<string, mixed> $args Associative array containing the result of `API_Interface::get_formatted_order_details()`, extracted into these variables:
 *
 * @var string $payment_status 'Awaiting Payment'|'Partially Paid'|'Paid'.
 * @var string $btc_address Destination payment address.
 * @var string $btc_total Order total in BTC.
 * @var string $btc_total_formatted Order total prefixed with "฿".
 * @var string $btc_exchange_rate_formatted // TODO: Format it! The Bitcoin exchange rate with friendly thousand separators.
 * @var string $btc_amount_received Amount received at the destination address so far.
 * @var string $btc_amount_received_formatted Amount received prefixed with "฿".
 * @var string $last_checked_time_formatted The last time a blockchain service was queried for updates to the payment address.
 * @var string $btc_address_derivation_path_sequence_number
 * @var string $parent_wallet_xpub_html
 *
 * @var string $exchange_rate_url
 * @var string $btc_exchange_rate
 *
 * @package    brianhenryie/bh-wp-bitcoin-gateway
 */

?>

<table>

	<tr>
		<td>Order Total:</td>
		<td><?php echo esc_html( $btc_total_formatted ); ?></td>
	</tr>

	<tr>
		<td>Exchange Rate:</td>
		<td><a target="_blank" href="<?php echo esc_url( $exchange_rate_url ); ?>"><?php echo esc_html( $btc_exchange_rate ); ?></a></td>
	</tr>

	<tr>
		<td>Payment Address:</td>
		<td><a target="_blank" href="<?php echo esc_url( "https://www.blockchain.com/btc/address/{$btc_address}" ); ?>"><?php echo esc_html( $btc_address ); ?></a></td>
	</tr>


	<tr>
		<td>Wallet Address:</td>
		<td><?php echo wp_kses_post( $parent_wallet_xpub_html ) . ' • 0/' . esc_html( $btc_address_derivation_path_sequence_number ); ?></a></td>
	</tr>

	<tr>
		<td>Transactions:</td>
		<td>
			<?php
			if ( empty( $transactions ) ) {
				echo esc_html__( 'No transactions yet', 'bh-wp-bitcoin-gateway' );
			} else {
				echo '<ul>';
				foreach ( $transactions as $transaction ) {
					echo '<li>' . esc_html( $transaction['time']->format( DATE_ATOM ) ) . ' – <a href="' . esc_url( "https://blockchain.com/explorer/transactions/btc/{$transaction['txid']}" ) . '" target="_blank">' . esc_html( $transaction['txid'] ) . '</a> – ' . esc_html( $transaction['value'] ) . ' </li>';
				}
				echo '</ul>';
			}
			?>
		</td>
	</tr>

	<tr>
		<td>Amount received:</td>
		<td><?php echo esc_html( $btc_amount_received_formatted ); ?></td>
	</tr>

	<tr>
		<td>Last Checked:</td>
		<td><?php echo esc_html( $last_checked_time_formatted ); ?></td>
	</tr>

</table>
