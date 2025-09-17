/**
 * External dependencies
 */
import React from 'react';
import { createRoot } from 'react-dom/client';

/**
 * Internal dependencies
 */
import { getAttributes, getContext } from '../../dataattributes';
import { getClassNameFromNamespacedName } from '../../names';

import blockJsonData from './block.json';
import { PaymentLastCheckedDisplay } from './payment-last-checked-display';

interface BlockMetadata {
	name: string;
	usesContext: string[];
	attributes: {
		[ key: string ]: {
			type: string;
			default: string | boolean | number;
		};
	};
}

const metadata = blockJsonData as BlockMetadata;

window.addEventListener( 'DOMContentLoaded', function () {
	const blockClassName = getClassNameFromNamespacedName( metadata.name );

	const elements: HTMLCollectionOf< Element > =
		document.getElementsByClassName( blockClassName );

	for ( let i = 0; i < elements.length; i++ ) {
		const element: Element = elements.item( i )!;

		const context = getContext( element, metadata.usesContext );
		const elementAttributes = getAttributes( element, metadata.attributes );

		const { lastCheckedTimeFormatted } = context;
		const { showLabel } = elementAttributes;

		// TODO: Remove class from element to prevent duplicate rendering?

		const root = createRoot( element );

		root.render(
			<React.StrictMode>
				<PaymentLastCheckedDisplay
					lastCheckedTimeFormatted={
						lastCheckedTimeFormatted as string
					}
					showLabel={ showLabel as boolean }
				/>
			</React.StrictMode>
		);
	}
} );
