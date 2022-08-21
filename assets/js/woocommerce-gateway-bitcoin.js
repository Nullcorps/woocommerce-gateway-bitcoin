(function( $ ) {
	'use strict';

	// the ajax response should have an optional redirect URL.
	// e.g. for a software purchase, redirect to the download page.

	$(function() {

		$('.woobtc_address').click(function(){

			var address = nullcorps_bitcoin_order_details.btc_address;

			// Copy it to the clipboard.
			navigator.clipboard.writeText(address);

			// Visual indication that the text has been copied.
			$(this).css('display', 'none');
			$(this).fadeIn('slow');
		});


		$('.woobtc_total').click(function(){

			var amount = nullcorps_bitcoin_order_details.btc_total;

			// Copy it to the clipboard.
			navigator.clipboard.writeText(amount);

			// Visual indication that the text has been copied.
			$(this).css('display', 'none');
			$(this).fadeIn('slow');
		});

		$('.woobtc_last_checked_time').click(function(){

			check_now();

		});

	});

	function check_now() {

		var ajax_url = nullcorps_ajax_data.ajax_url;
		var nonce = nullcorps_ajax_data.nonce;

		var order_id = nullcorps_bitcoin_order_details.order_id;

		// Let's fade out the numbers to indicate they are maybe about to be updated.
		$('.woobtc_updatable').animate({ opacity: 0.4 });

		var data = {
			'action': 'nullcorps_bitcoin_refresh_order_details',
			'_ajax_nonce': nonce,
			'order_id': order_id
		};

		jQuery.post(ajax_url, data, function(response) {

			// Compare the existing totals,
			// If they are the same, just reset opacity,
			// If they are different, display:none the slow fade in.

			var new_nullcorps_bitcoin_order_details = response.data;

			if( nullcorps_bitcoin_order_details.btc_amount_received !== new_nullcorps_bitcoin_order_details.btc_amount_received ) {
				// We have a new payment!
				$('.woobtc_updatable').css('display', 'none');

				$('.woobtc_status').text( new_nullcorps_bitcoin_order_details.status );
				$('.woobtc_amount_received').text( new_nullcorps_bitcoin_order_details.amount_received );
				$('.order-status').text( new_nullcorps_bitcoin_order_details.order_status_formatted );

				// TODO: Transactions.

				$('.woobtc_updatable').fadeIn('slow');

			} else {
				// Return to regular opacity
				$('.woobtc_updatable').animate({ opacity: 1.0 });
			}

			$('.woobtc_last_checked_time').text( new_nullcorps_bitcoin_order_details.last_checked_time_formatted );
			
			nullcorps_bitcoin_order_details = response.data;
		});


	}

})( jQuery );
