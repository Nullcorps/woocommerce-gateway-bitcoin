/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import React from 'react';

/**
 * Internal dependencies
 */
import { ExchangeRateDisplay } from './exchange-rate-display';

interface SaveProps {
	attributes: {
		showLabel: boolean;
	};
}

export const Save: React.FC<SaveProps> = ({ attributes }) => {
	const { showLabel } = attributes;
	
	const blockProps = useBlockProps.save({
		className: 'bh-wp-bitcoin-gateway-exchange-rate-block',
	});

	return (
		<div {...blockProps}>
			<ExchangeRateDisplay showLabel={showLabel} />
		</div>
	);
};