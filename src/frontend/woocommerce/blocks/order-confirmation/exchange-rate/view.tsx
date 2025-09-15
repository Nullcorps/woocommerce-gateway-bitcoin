// assets/js/frontend/woocommerce/blocks/order-confirmation/exchange-rate/exchange-rate-block.min.js
//       src/frontend/woocommerce/blocks/order-confirmation/exchange-rate/view.tsx

/**
 * External dependencies
 */
import React from 'react';
import { createRoot } from 'react-dom/client';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { ExchangeRateDisplay } from './exchange-rate-display';
import { getClassNameFromNamespacedName } from "../../names";
import { getAttributes, getContext } from "../../dataattributes";

window.addEventListener( 'DOMContentLoaded', function () {

	const blockClassName = getClassNameFromNamespacedName( metadata.name );

	const elements: HTMLCollectionOf< Element > =
		document.getElementsByClassName( blockClassName );

	for ( let i = 0; i < elements.length; i++ ) {
		const element: Element = elements.item( i )!;

		const context = getContext( element, metadata.usesContext );
		const elementAttributes = getAttributes( element, metadata.attributes );

		const { btcExchangeRateFormatted, exchangeRateUrl } = context;
		const { showLabel, useUrl } = elementAttributes;

		// TODO: Remove class from element to prevent duplicate rendering?

		const root = createRoot( element );

		root.render(
			<React.StrictMode>
				<ExchangeRateDisplay
					exchangeRate={ btcExchangeRateFormatted as string }
					showLabel={ showLabel as boolean }
					useUrl={ useUrl as boolean }
					exchangeRateUrl={ exchangeRateUrl as string }
				/>
			</React.StrictMode>
		);
	}
} );
