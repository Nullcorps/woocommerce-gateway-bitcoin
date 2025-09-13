/**
 * WordPress dependencies
 */
/**
 * External dependencies
 */
import React from 'react';

import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { PaymentStatusDisplay } from './payment-status-display';

interface SaveProps {
	attributes: {
		showLabel: boolean;
	};
}

export const Save: React.FC< SaveProps > = ( { attributes } ) => {
	const { showLabel } = attributes;

	const blockProps = useBlockProps.save( {
		className: 'bh-wp-bitcoin-gateway-payment-status-block',
	} );

	return (
		<div { ...blockProps }>
			<PaymentStatusDisplay showLabel={ showLabel } />
		</div>
	);
};
