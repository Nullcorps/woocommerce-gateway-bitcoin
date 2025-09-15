/**
 * WordPress dependencies
 */
/**
 * External dependencies
 */
import React, { useEffect, useState } from 'react';

import { __ } from '@wordpress/i18n';

interface PaymentAddressDisplayProps {
	showLabel?: boolean;
	isPreview?: boolean;
	orderId?: number;
}

export const PaymentAddressDisplay: React.FC< PaymentAddressDisplayProps > = ( {
	showLabel = true,
	isPreview = false,
} ) => {
	const [ paymentAddress, setPaymentAddress ] = useState< string >( '' );

	useEffect( () => {
		if ( isPreview ) {
			setPaymentAddress( 'xpub1234' );
		}
	}, [] );

	// This isn't really being used.
	return (
		<div className="bh-wp-bitcoin-gateway-payment-address-block">
			{ showLabel && (
				<span className="payment-address-label">
					{ __( 'Payment address:', 'bh-wp-bitcoin-gateway' ) }
				</span>
			) }
			<span className="payment-address-value">{ paymentAddress }</span>
		</div>
	);
};
