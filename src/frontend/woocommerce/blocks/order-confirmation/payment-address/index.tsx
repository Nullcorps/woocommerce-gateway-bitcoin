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
	// ancestor: [ 'bh-wp-bitcoin-gateway/order-confirmation' ], // I think this should be in block.json
	save: () => null,
} );
