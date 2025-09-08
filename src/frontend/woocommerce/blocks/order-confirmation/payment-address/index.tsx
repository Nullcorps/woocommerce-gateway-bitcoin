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
import { Save } from './save';

registerBlockType(metadata.name, {
	...metadata,
  // icon:
	edit: Edit,
	// save: Save,
  // script: PaymentAddressDisplay,
  // parent: [ 'bh-wp-bitcoin-gateway/order-confirmation' ],
  save: () => null,
});