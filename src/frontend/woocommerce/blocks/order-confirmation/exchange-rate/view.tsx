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


	const contextItemNames: string[] = metadata.usesContext;
	const attributes = metadata.attributes as {
		[ key: string ]: {
			type: string;
			default: string | boolean | number;
		};
	};

	// block.json metadata.name
	// bh-wp-bitcoin-gateway/exchange-rate-block
	// 'bh-wp-bitcoin-gateway-exchange-rate-block';
	const blockClassName = getClassNameFromNamespacedName( metadata.name );

	const elements: HTMLCollectionOf< Element > =
		document.getElementsByClassName( blockClassName );

	for ( let i = 0; i < elements.length; i++ ) {
		const element: Element = elements.item( i )!;

		const context = getContext( element, contextItemNames );
		const elementAttributes = getAttributes( element, attributes );

    // TODO: pass the variables using CamleCase from the PHP side.
		const exchangeRate = context.btc_exchange_rate_formatted;
		const exchangeRateUrl = context.exchange_rate_url;

		const { showLabel, useUrl } = elementAttributes;

		// TODO: Remove class from element to prevent duplicate rendering?

		const root = createRoot( element );

		root.render(
			<React.StrictMode>
				<ExchangeRateDisplay
					exchangeRate={ exchangeRate as string }
					showLabel={ showLabel as boolean }
					useUrl={ useUrl as boolean }
					exchangeRateUrl={ exchangeRateUrl as string }
				/>
			</React.StrictMode>
		);
	}
} );
