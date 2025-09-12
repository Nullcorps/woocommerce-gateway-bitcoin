/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { Edit } from './edit';

// registerBlockType(metadata.name, {
// 	...metadata,
//   // icon:
// 	edit: Edit,
// 	// save: Save,
//   save: () => null,
// });



registerBlockType(metadata.name, {
  ...metadata,
    title: 'Bitcoin test exchange rate',
    icon: "smiley",
    usesContext: ['bh-wp-bitcoin-gateway/orderId'],
    supports: {
        // Removes support for an HTML mode.
        html: false,
    },
    edit: Edit,
    // edit: ({ context })=> {
    //     console.log(' Bitcoinexchange rate js block');
    //     // return 'The record ID: ' + context['bh-wp-bitcoin-gateway/orderId'];
    //     return 'The record ID:';
    // },
    // save: () => null,
    // save: Save,
    save: ()=> {
      return (
        <span className="bh-wp-bitcoin-gateway-exchange-rate-block" />
      );
    },
});
