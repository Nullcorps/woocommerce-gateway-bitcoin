// assets/js/frontend/woocommerce/blocks/order-confirmation/exchange-rate/exchange-rate-block.min.js
//       src/frontend/woocommerce/blocks/order-confirmation/exchange-rate/view.tsx

/**
 * React dependencies
 */
import React from 'react';
import ReactDOM from 'react-dom/client';

/**
 * WordPress dependencies?
 */
import metadata from './block.json';

/**
 * Internal dependencies
 */
import { ExchangeRateDisplay } from './exchange-rate-display';

console.log('Exchange Rate View');

window.addEventListener('DOMContentLoaded', function() {
  console.log('DOMContentLoaded (Exchange Rate View)');
  function getClassNameFromNamespacedName(namespacedName: string): string {
    return namespacedName.replace(/\//g, '-');
  }

  function getLocalNameFromNamespacedName(namespacedName: string): string {
    return namespacedName.split('\/')[1];
  }

  const contextItemNames: string[] = metadata.usesContext
  const attributes = metadata.attributes

  function getAttributes(element: Element): { [key: string]: string | boolean | number }  {

    var attributeValues = {};

    console.log("Getting attributes for element: ", element);

    Object.entries(attributes).forEach(([name,v]) => {
      console.log("The key: ", name)
      console.log("The value: ", v)

      const dataAttr = 'data-attribute-' + name.toLowerCase();
      console.log("searching for : ", dataAttr);
      const attrValue = element.getAttribute(dataAttr);
      console.log("found : ", attrValue);
      if (attrValue) {
        console.log(name + ':' + attrValue)
        if(attributes[name].type === 'boolean') {
          attributeValues[name] = attrValue === 'true';
        }else if(attributes[name].type === 'numeric') {
          attributeValues[name] = parseFloat(attrValue);
        }else{
          attributeValues[name] = attrValue;
        }
      } else {
        attributeValues[name] = attributes[name].default;
      }
    });

    return attributeValues;
  }
  function getContext(element: Element): { [key: string]: string | boolean | number }  {
    var context: { [key: string]: string | boolean | number } = {};

    // Get context from ancestor data attributes
    contextItemNames.forEach((name: string) => {

      var parent = element.parentElement;
      while (parent) {
        const dataAttr = 'data-context-' + getClassNameFromNamespacedName(name);
        const attrValue = parent.getAttribute(dataAttr);
        if (attrValue) {
          context[getLocalNameFromNamespacedName(name)] = attrValue;
          console.log(getLocalNameFromNamespacedName(name) + ':' + attrValue)
          break;
        }
        parent = parent.parentElement;
      }
      if (!context['bh-wp-bitcoin-gateway/' + name]) {
        console.log(`Context attribute ${name} not found in ancestors of`, element);
        console.warn(`Context attribute ${name} not found in ancestors of`, element);
      } else {
        console.log(`Context attribute ${name} found: ${context[name]}`);
      }
    });
    return context;
  }

// block.json metadata.name
// bh-wp-bitcoin-gateway/exchange-rate-block
// 'bh-wp-bitcoin-gateway-exchange-rate-block';
  const blockClassName = getClassNameFromNamespacedName(metadata.name);

  const elements: HTMLCollectionOf<Element> = document.getElementsByClassName(blockClassName);

  console.log(elements.length + ' elements found with class ' + blockClassName);

  for (var i = 0; i < elements.length; i++) {
    const element: Element = elements.item(i)!;

    const context = getContext(element);
    const attributes = getAttributes(element);

    const exchangeRate = context.btc_exchange_rate_formatted;
    const exchangeRateUrl = context.exchange_rate_url;

    const { showLabel, useUrl } = attributes;

    console.log(`Context exchangeRate is ${exchangeRate}`);
    console.log(`attribute showLabel is ${showLabel}`);
    console.log(`attribute useUrl is ${useUrl}`);
    console.log(`Context exchangeRateUrl is ${exchangeRateUrl}`);

    // TODO: Remove class from element to prevent duplicate rendering?

    const root = ReactDOM.createRoot(element);

    root.render(
      <React.StrictMode>
        <ExchangeRateDisplay exchangeRate={exchangeRate} showLabel={showLabel} useUrl={useUrl} exchangeRateUrl={exchangeRateUrl} />
      </React.StrictMode>
    );
  }
});