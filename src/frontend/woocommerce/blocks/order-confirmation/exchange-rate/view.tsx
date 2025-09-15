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

window.addEventListener( 'DOMContentLoaded', function () {
	function getClassNameFromNamespacedName( namespacedName: string ): string {
		return namespacedName.replace( /\//g, '-' );
	}

	function getLocalNameFromNamespacedName( namespacedName: string ): string {
		return namespacedName.split( '/' )[ 1 ];
	}

	const contextItemNames: string[] = metadata.usesContext;
	const attributes = metadata.attributes as {
		[ key: string ]: {
			type: string;
			default: string | boolean | number;
		};
	};

	function getAttributes( element: Element ): {
		[ key: string ]: string | boolean | number;
	} {
		const attributeValues: { [ key: string ]: string | boolean | number } =
			{};

		// Cast numbers and bools to their proper types.
		Object.entries( attributes ).forEach( ( [ name, v ] ) => {
			const dataAttr = 'data-attribute-' + name.toLowerCase();
			const attrValue = element.getAttribute( dataAttr );
			if ( attrValue ) {
				if ( attributes[ name ].type === 'boolean' ) {
					attributeValues[ name ] = attrValue === 'true';
				} else if ( attributes[ name ].type === 'numeric' ) {
					attributeValues[ name ] = parseFloat( attrValue );
				} else {
					attributeValues[ name ] = attrValue;
				}
			} else {
				attributeValues[ name ] = attributes[ name ].default;
			}
		} );

		return attributeValues;
	}
	function getContext( element: Element ): {
		[ key: string ]: string | boolean | number;
	} {
		const context: { [ key: string ]: string | boolean | number } = {};

		// Get context from ancestor data attributes
		contextItemNames.forEach( ( name: string ) => {
			let parent = element.parentElement;
			while ( parent ) {
				const dataAttr =
					'data-context-' + getClassNameFromNamespacedName( name );
				const attrValue = parent.getAttribute( dataAttr );
				if ( attrValue ) {
					context[ getLocalNameFromNamespacedName( name ) ] = attrValue;
					break;
				}
				parent = parent.parentElement;
			}
			if ( ! context[ 'bh-wp-bitcoin-gateway/' + name ] ) {
				console.log(
					`Context attribute ${ name } not found in ancestors of`,
					element
				);
				console.warn(
					`Context attribute ${ name } not found in ancestors of`,
					element
				);
			} else {
				console.log(
					`Context attribute ${ name } found: ${ context[ name ] }`
				);
			}
		} );
		return context;
	}

	// block.json metadata.name
	// bh-wp-bitcoin-gateway/exchange-rate-block
	// 'bh-wp-bitcoin-gateway-exchange-rate-block';
	const blockClassName = getClassNameFromNamespacedName( metadata.name );

	const elements: HTMLCollectionOf< Element > =
		document.getElementsByClassName( blockClassName );

	for ( let i = 0; i < elements.length; i++ ) {
		const element: Element = elements.item( i )!;

		const context = getContext( element );
		const elementAttributes = getAttributes( element );

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
