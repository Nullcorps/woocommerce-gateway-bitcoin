/**
 * WordPress dependencies
 */
/**
 * External dependencies
 */
import React, { useEffect } from 'react';

import {
	useBlockProps,
	InnerBlocks,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { detectOrderId } from './order-detection';

interface EditProps {
	attributes: {
		orderId: number;
	};
	setAttributes: ( attributes: Partial< { orderId: number } > ) => void;
	context: Record< string, any >;
}

export const Edit: React.FC< EditProps > = ( {
	attributes,
	setAttributes,
} ) => {
	const { orderId } = attributes;

	const blockProps = useBlockProps( {
		className: 'bh-wp-bitcoin-gateway-bitcoin-order-container',
	} );

	return (
		// <div { ...blockProps }>
		//   <InnerBlocks defaultBlock={['core/paragraph', {placeholder: "Lorem ipsum..."}]} directInsert />
		// </div>
		<div { ...blockProps }>
			<div className="wp-block-group">
				<div className="wp-block-group__inner-container">
					<InnerBlocks
						templateLock={ false }
						renderAppender={ InnerBlocks.DefaultBlockAppender }
						// defaultBlock={['core/paragraph', {placeholder: "Lorem ipsum..."}]}
						// directInsert
					/>
				</div>
			</div>
		</div>
	);
	//
	// 		<div {...blockProps}>
	// 			<div className="wp-block-group">
	// 				<div className="wp-block-group__inner-container">
	// 					<InnerBlocks
	// 						templateLock={false}
	// 						renderAppender={InnerBlocks.DefaultBlockAppender}
	// 					/>
	// 				</div>
	// 			</div>
	// 		</div>
	// 	</>
	// );
};

//
// export const Edit: React.FC<EditProps> = ({ attributes, setAttributes }) => {
// 	const { orderId } = attributes;
//
// 	const blockProps = useBlockProps({
// 		className: 'bh-wp-bitcoin-gateway-bitcoin-order-container',
// 	});
//
// 	// Detect order ID from URL parameters on the editor if we're on a thank you page
// 	useEffect(() => {
// 		if (!orderId) {
// 			const detectedOrderId = detectOrderId();
// 			if (detectedOrderId > 0) {
// 				setAttributes({ orderId: detectedOrderId });
// 			}
// 		}
// 	}, [orderId, setAttributes]);
//
// 	return (
// 		<>
// 			<InspectorControls>
// 				<PanelBody title={__('Bitcoin Order Settings', 'bh-wp-bitcoin-gateway')}>
// 					<p>{__('This block automatically detects the order ID from the Thank You page URL and provides it to inner blocks.', 'bh-wp-bitcoin-gateway')}</p>
// 					{orderId > 0 && (
// 						<p><strong>{__('Current Order ID:', 'bh-wp-bitcoin-gateway')} {orderId}</strong></p>
// 					)}
// 					{orderId === 0 && (
// 						<p><em>{__('Order ID will be detected automatically on the Thank You page.', 'bh-wp-bitcoin-gateway')}</em></p>
// 					)}
// 				</PanelBody>
// 			</InspectorControls>
//
// 			<div {...blockProps}>
// 				<div className="wp-block-group">
// 					<div className="wp-block-group__inner-container">
// 						<InnerBlocks
// 							templateLock={false}
// 							renderAppender={InnerBlocks.DefaultBlockAppender}
// 						/>
// 					</div>
// 				</div>
// 			</div>
// 		</>
// 	);
// };
//
