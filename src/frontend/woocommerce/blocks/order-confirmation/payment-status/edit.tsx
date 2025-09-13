/**
 * WordPress dependencies
 */
/**
 * External dependencies
 */
import React from 'react';

import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { PaymentStatusDisplay } from './payment-status-display';

interface EditProps {
	attributes: {
		orderId: number;
		showLabel: boolean;
	};
	setAttributes: (
		attributes: Partial< { orderId: number; showLabel: boolean } >
	) => void;
	context: {
		'bh-wp-bitcoin-gateway/orderId'?: number;
	};
}

export const Edit: React.FC< EditProps > = ( {
	attributes,
	setAttributes,
	context,
} ) => {
	const { showLabel, orderId } = attributes;
	const contextOrderId = context[ 'bh-wp-bitcoin-gateway/orderId' ];

	// Use context order ID if available, otherwise fall back to attribute
	const effectiveOrderId = contextOrderId || orderId || 123;

	const blockProps = useBlockProps( {
		className: 'bh-wp-bitcoin-gateway-payment-status-block',
	} );

	console.log( '(edit)orderId' + orderId );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Payment Status Settings',
						'bh-wp-bitcoin-gateway'
					) }
				>
					<ToggleControl
						label={ __( 'Show label', 'bh-wp-bitcoin-gateway' ) }
						checked={ showLabel }
						onChange={ ( value ) =>
							setAttributes( { showLabel: value } )
						}
					/>
					{ effectiveOrderId > 0 && (
						<p>
							<strong>
								{ __( 'Order ID:', 'bh-wp-bitcoin-gateway' ) }{ ' ' }
								{ effectiveOrderId }
							</strong>
						</p>
					) }
					{ contextOrderId && (
						<p>
							<em>
								{ __(
									'(Using order ID from container block)',
									'bh-wp-bitcoin-gateway'
								) }
							</em>
						</p>
					) }
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<PaymentStatusDisplay
					showLabel={ showLabel }
					isPreview={ true }
					orderId={ effectiveOrderId }
				/>
				<p
					className="description"
					style={ {
						fontSize: '12px',
						opacity: 0.7,
						marginTop: '4px',
					} }
				>
					{ __(
						'Preview - actual status will display on order confirmation pages',
						'bh-wp-bitcoin-gateway'
					) }
				</p>
			</div>
		</>
	);
};
