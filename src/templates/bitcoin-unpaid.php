<?php
/**
 * Template displaying a table with the Bitcoin address, QR code, amount required, amount received, status, and the time
 * last checked. CSS classes on each allow for JS to target the data for copying to the clipboard.
 *
 * @see \Nullcorps\WC_Gateway_Bitcoin\API\API_Interface::get_order_details()
 *
 * @var array<string, mixed> $args Associative array containing the result of `API_Interface::get_order_details()`, extracted into these variables:
 *
 * @var string $btc_logo_url // TODO
 * @var string $status 'Awaiting Payment'|'Partially Paid'|'Paid'.
 * @var string $btc_address Destination payment address.
 * @var string $btc_total Order total in BTC.
 * @var string $btc_total_formatted Order total prefixed with "฿".
 * @var string $btc_exchange_rate_formatted // TODO: Format it! The Bitcoin exchange rate with friendly thousand separators.
 * @var string $btc_amount_received Amount received at the destination address so far.
 * @var string $btc_amount_received_formatted Amount received prefixed with "฿".
 * @var string $last_checked_time_formatted The last time a blockchain service was queried for updates to the payment address.
 *
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

use Nullcorps\WC_Gateway_Bitcoin\chillerlan\QRCode\QRCode;

$qr_address = 'bitcoin:' . $btc_address . '?amount=' . $btc_total;

$btc_logo_url = NULLCORPS_WOOCOMMERCE_GATEWAY_BITCOIN_URL . '/assets/bitcoin.png';
?>

<div class="woobtc-details">

	<?php // For scrolling to? ?>
	<a id="woobtc"></a>

	<div class="btc_logo_qr">
	<img alt="Bitcoin logo" class="woobtc_bitcoin_logo" src="<?php echo esc_attr( $btc_logo_url ); ?>">

	<a href="<?php echo esc_attr( $qr_address ); ?>">
		<img src="<?php echo esc_attr( ( new QRCode() )->render( $qr_address ) ); ?>" alt="<?php esc_attr_e( 'Payment QR Code', 'nullcorps-wc-gateway-bitcoin' ); ?>" />
	</a>
	</div>

	<table>
		<tr>
			<td><span class=""><?php esc_html_e( 'Payment Address:', 'nullcorps-wc-gateway-bitcoin' ); ?></span></td>
			<td><span class="woobtc_address"><?php echo esc_html( $btc_address ); ?></span></td>
		</tr>
		<tr>
			<td><span class=""><?php esc_html_e( 'Payment Total:', 'nullcorps-wc-gateway-bitcoin' ); ?></span></td>
			<td><span class="woobtc_total"><?php echo esc_html( $btc_total_formatted ); ?></span></td>
		</tr>
		<tr>
			<td><span class=""><?php esc_html_e( 'Amount Received:', 'nullcorps-wc-gateway-bitcoin' ); ?></span></td>
			<td><span class="woobtc_amount_received woobtc_updatable"><?php echo esc_html( $btc_amount_received_formatted ); ?></span></td>
		</tr>
		<tr>
			<td><span class=""><?php esc_html_e( 'Status:', 'nullcorps-wc-gateway-bitcoin' ); ?></span></td>
			<td><span class="woobtc_status woobtc_updatable">
			<?php
			echo esc_html( $status );
			?>
			</span></td>
		</tr>
		<tr>
			<td><span class=""><?php esc_html_e( 'Last Checked:', 'nullcorps-wc-gateway-bitcoin' ); ?></span></td>
			<td><span class="woobtc_last_checked_time woobtc_updatable">
				<?php echo esc_html( $last_checked_time_formatted ); ?></span>
			</td>
		</tr>

	</table>


	<p>NB: Please only send <i>Bitcoin</i>, which always has the ticker BTC, not any of the many clones. If you send coins other than Bitcoin (e.g. Bitcoin Cash) then those coins will be lost and your order will still not be paid.</p>


	<p>Exchange rate at time of order: 1 BTC = <?php echo $btc_exchange_rate_formatted; ?></p>

</div>

