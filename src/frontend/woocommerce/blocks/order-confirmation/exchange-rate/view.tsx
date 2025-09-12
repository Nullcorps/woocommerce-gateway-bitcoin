// assets/js/frontend/woocommerce/blocks/order-confirmation/exchange-rate/exchange-rate-block.min.js
//       src/frontend/woocommerce/blocks/order-confirmation/exchange-rate/view.tsx

/**
 * React dependencies
 */
import React from 'react';
import ReactDOM from 'react-dom/client';

/**
 * WordPress dependencies
 */
import metadata from './block.json';

/**
 * Internal dependencies
 */
import { ExchangeRateDisplay } from './exchange-rate-display';

console.log('Exchange Rate View');


function getClassNameFromNamespacedName( namespacedName: string ): string {
  return namespacedName.replace( /\//g, '-' );
}
function getLocalNameFromNamespacedName( namespacedName: string ): string {
  return namespacedName.split( '\/')[1];
}

// block.json metadata.name
// bh-wp-bitcoin-gateway/exchange-rate-block
// 'bh-wp-bitcoin-gateway-exchange-rate-block';
const blockClassName = getClassNameFromNamespacedName(metadata.name);

const contextItemNames: string[] = metadata.usesContext

const elements: HTMLCollectionOf<Element> = document.getElementsByClassName( blockClassName );

for (var i = 0; i < elements.length; i++) {
  const element: Element = elements.item(i)!;

  var context: { [key: string]: string|boolean|number } = {};

  // Get context from ancestor data attributes
  contextItemNames.forEach((name: string) => {
      var parent = element.parentElement;
      while (parent) {
          const dataAttr = 'data-' + getClassNameFromNamespacedName( name );
          const attrValue = parent.getAttribute(dataAttr);
          if (attrValue) {
              context[getLocalNameFromNamespacedName(name)] = attrValue;
              break;
          }
          parent = parent.parentElement;
      }
      if(!context['bh-wp-bitcoin-gateway/' + name]) {
          console.warn(`Context attribute ${name} not found in ancestors of`, element);
      }
  });

  const {showLabel, orderId} = context;

  // Remove class from element to prevent duplicate rendering?

  const root = ReactDOM.createRoot(element);

  root.render(
    <React.StrictMode>
      <ExchangeRateDisplay showLabel={showLabel === 'true'} isPreview={false} orderId={parseInt(orderId)} />
    </React.StrictMode>
  );
}