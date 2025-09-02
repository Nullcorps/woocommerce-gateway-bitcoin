/**
 * WordPress dependencies
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import React from 'react';

interface SaveProps {
	attributes: {
	};
}

export const Save: React.FC<SaveProps> = () => {
	const blockProps = useBlockProps.save({
		className: 'bh-wp-bitcoin-gateway-bitcoin-order-container',
	});

	return (
		<div {...blockProps}>
			<div className="wp-block-group">
				<div className="wp-block-group__inner-container">
					<InnerBlocks.Content />
				</div>
			</div>
		</div>
	);
};