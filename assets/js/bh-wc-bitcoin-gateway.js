(function( $ ) {
	'use strict';

	// the ajax response should have an optional redirect URL.
	// e.g. for a software purchase, redirect to the download page.

	$(function() {

		$('.bh_wc_bitcoin_gateway_address').click(function(){

			var address = bh_wc_bitcoin_gateway_order_details.btc_address;

			// Copy it to the clipboard.
			navigator.clipboard.writeText(address);

			// Visual indication that the text has been copied.
			$(this).css('display', 'none');
			$(this).fadeIn('slow');
		});


		$('.bh_wc_bitcoin_gateway_total').click(function(){

			var amount = bh_wc_bitcoin_gateway_order_details.btc_total;

			// Copy it to the clipboard.
			navigator.clipboard.writeText(amount);

			// Visual indication that the text has been copied.
			$(this).css('display', 'none');
			$(this).fadeIn('slow');
		});

		$('.bh_wc_bitcoin_gateway_last_checked_time').click(function(){

			check_now();

		});

	});

	function check_now() {

		var ajax_url = bh_wc_bitcoin_gateway_ajax_data.ajax_url;
		var nonce = bh_wc_bitcoin_gateway_ajax_data.nonce;

		var order_id = bh_wc_bitcoin_gateway_order_details.order_id;

		// Let's fade out the numbers to indicate they are maybe about to be updated.
		$('.bh_wc_bitcoin_gateway_updatable').animate({ opacity: 0.4 });

		$('.bh-wc-bitcoin-gateway-details').addClass('blockUI');

		var data = {
			'action': 'bh_wc_bitcoin_gateway_refresh_order_details',
			'_ajax_nonce': nonce,
			'order_id': order_id
		};

		jQuery.post(ajax_url, data, function(response) {

			// Compare the existing totals,
			// If they are the same, just reset opacity,
			// If they are different, display:none the slow fade in.

			var new_bh_wc_bitcoin_gateway_order_details = response.data;

			if( bh_wc_bitcoin_gateway_order_details.btc_amount_received !== new_bh_wc_bitcoin_gateway_order_details.btc_amount_received ) {
				// We have a new payment!
				$('.bh_wc_bitcoin_gateway_updatable').css('display', 'none');

				$('.bh_wc_bitcoin_gateway_status').text( new_bh_wc_bitcoin_gateway_order_details.status );
				$('.bh_wc_bitcoin_gateway_amount_received').text( new_bh_wc_bitcoin_gateway_order_details.amount_received );
				$('.order-status').text( new_bh_wc_bitcoin_gateway_order_details.order_status_formatted );

				// TODO: Transactions.

				$('.bh_wc_bitcoin_gateway_updatable').fadeIn('slow');

			} else {
				// Return to regular opacity
				$('.bh_wc_bitcoin_gateway_updatable').animate({ opacity: 1.0 });
			}

			$('.bh_wc_bitcoin_gateway_last_checked_time').text( new_bh_wc_bitcoin_gateway_order_details.last_checked_time_formatted );

			$('.bh-wc-bitcoin-gateway-details').removeClass('blockUI');

			for (var key in Object.keys(response.data)) {
				bh_wc_bitcoin_gateway_order_details[key] = response.data[key];
			}

		});

	}

})( jQuery );
