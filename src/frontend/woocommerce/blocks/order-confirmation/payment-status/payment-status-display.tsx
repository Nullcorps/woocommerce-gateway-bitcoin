/**
 * WordPress dependencies
 */
/**
 * External dependencies
 */
import React, { useEffect, useState } from 'react';

import { __ } from '@wordpress/i18n';

interface PaymentStatusDisplayProps {
	showLabel?: boolean;
	isPreview?: boolean;
	orderId?: number;
}

export const PaymentStatusDisplay: React.FC< PaymentStatusDisplayProps > = ( {
	showLabel = true,
	isPreview = false,
	orderId = null,
} ) => {
	const [ paymentStatus, setPaymentStatus ] = useState< string >( '' );
	const [ loading, setLoading ] = useState( ! isPreview );

	console.log( 'orderId' + orderId );

	useEffect( () => {
		if ( isPreview ) {
			setPaymentStatus( 'Awaiting payment' );
		}
	}, [] );

	// This isn't really being used.
	return (
		<div className="bh-wp-bitcoin-gateway-payment-status-block">
			{ showLabel && (
				<span className="payment-status-label">
					{ __( 'Payment status:', 'bh-wp-bitcoin-gateway' ) }
				</span>
			) }
			<span className="payment-status-value">{ paymentStatus }</span>
		</div>
	);
};
