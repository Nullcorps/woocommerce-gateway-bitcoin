/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { Edit } from './edit';

registerBlockType(metadata.name, {
  ...metadata,
    title: 'Bitcoin test exchange rate',
    icon: "smiley",
    usesContext: [
      'bh-wp-bitcoin-gateway/exchangeRate',
      'bh-wp-bitcoin-gateway/exchangeRateUrl'
    ],
    supports: {
        // Removes support for an HTML mode.
        html: false,
    },
    edit: Edit,
    save: ({ attributes })=> {

      const { showLabel, useUrl } = attributes;

      return (
        <span className="bh-wp-bitcoin-gateway-exchange-rate" data-attribute-showLabel={showLabel} data-attribute-useUrl={useUrl}/>
      );
    },
});
