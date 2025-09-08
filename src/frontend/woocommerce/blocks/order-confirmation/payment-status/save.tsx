/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import React from 'react';

/**
 * Internal dependencies
 */
import { PaymentStatusDisplay } from './payment-status-display';

interface SaveProps {
	attributes: {
		showLabel: boolean;
	};
}

export const Save: React.FC<SaveProps> = ({ attributes }) => {
	const { showLabel } = attributes;
	
	const blockProps = useBlockProps.save({
		className: 'bh-wp-bitcoin-gateway-payment-status-block',
	});

	return (
		<div {...blockProps}>
			<PaymentStatusDisplay showLabel={showLabel} />
		</div>
	);
};