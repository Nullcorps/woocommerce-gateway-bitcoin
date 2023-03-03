/* global navigator, jQuery, bh_wp_bitcoin_gateway_order_details, bh_wp_bitcoin_gateway_ajax_data */
( function( $ ) {
	'use strict';

	// the ajax response should have an optional redirect URL.
	// e.g. for a software purchase, redirect to the download page.

	$( function() {
		$( '.bh_wp_bitcoin_gateway_address' ).click( function() {
			const address = bh_wp_bitcoin_gateway_order_details.btc_address;

			// Copy it to the clipboard.
			navigator.clipboard.writeText( address );

			// Visual indication that the text has been copied.
			$( this ).css( 'display', 'none' );
			$( this ).fadeIn( 'slow' );
		} );

		$( '.bh_wp_bitcoin_gateway_total' ).click( function() {
			const amount = bh_wp_bitcoin_gateway_order_details.btc_total;

			// Copy it to the clipboard.
			navigator.clipboard.writeText( amount );

			// Visual indication that the text has been copied.
			$( this ).css( 'display', 'none' );
			$( this ).fadeIn( 'slow' );
		} );

		$( '.bh_wp_bitcoin_gateway_last_checked_time' ).click( function() {
			checkNow();
		} );
	} );

	function checkNow() {
		const ajaxUrl = bh_wp_bitcoin_gateway_ajax_data.ajax_url;
		const nonce = bh_wp_bitcoin_gateway_ajax_data.nonce;

		const orderId = bh_wp_bitcoin_gateway_order_details.order_id;

		// Let's fade out the numbers to indicate they are maybe about to be updated.
		$( '.bh_wp_bitcoin_gateway_updatable' ).animate( { opacity: 0.4 } );

		$( '.bh-wp-bitcoin-gateway-details' ).addClass( 'blockUI' );

		const data = {
			action: 'bh_wp_bitcoin_gateway_refresh_order_details',
			_ajax_nonce: nonce,
			order_id: orderId,
		};

		$.post( ajaxUrl, data, function( response ) {
			// Compare the existing totals,
			// If they are the same, just reset opacity,
			// If they are different, display:none the slow fade in.

			const newData = response.data;

			if (
				bh_wp_bitcoin_gateway_order_details.btc_amount_received !==
				newData.btc_amount_received
			) {
				// We have a new payment!
				$( '.bh_wp_bitcoin_gateway_updatable' ).css(
					'display',
					'none'
				);

				$( '.bh_wp_bitcoin_gateway_status' ).text( newData.status );
				$( '.bh_wp_bitcoin_gateway_amount_received' ).text(
					newData.amount_received
				);
				$( '.order-status' ).text( newData.order_status_formatted );

				// TODO: Transactions.

				$( '.bh_wp_bitcoin_gateway_updatable' ).fadeIn( 'slow' );
			} else {
				// Return to regular opacity
				$( '.bh_wp_bitcoin_gateway_updatable' ).animate( {
					opacity: 1.0,
				} );
			}

			$( '.bh_wp_bitcoin_gateway_last_checked_time' ).text(
				newData.last_checked_time_formatted
			);

			$( '.bh-wp-bitcoin-gateway-details' ).removeClass( 'blockUI' );

			for ( const key in Object.keys( response.data ) ) {
				bh_wp_bitcoin_gateway_order_details[ key ] =
					response.data[ key ];
			}
		} );
	}
} )( jQuery );
