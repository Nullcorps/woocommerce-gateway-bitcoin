/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { Edit } from './edit';

registerBlockType( metadata.name, {
	...metadata,
	// icon:
	edit: Edit,
	// save: Save,
	// ancestor: [ 'bh-wp-bitcoin-gateway/order-confirmation' ], // -> .json
	save: () => null,
} );
