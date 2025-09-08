/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import React from 'react';

/**
 * Internal dependencies
 */
import { PaymentAddressDisplay } from './payment-address-display';

interface SaveProps {
	attributes: {
		showLabel: boolean;
	};
}

export const Save: React.FC<SaveProps> = ({ attributes }) => {
	const { showLabel } = attributes;
	
	const blockProps = useBlockProps.save({
		className: 'bh-wp-bitcoin-gateway-payment-address-block',
	});

	return (
		<div {...blockProps}>
			<PaymentAddressDisplay showLabel={showLabel} />
		</div>
	);
};