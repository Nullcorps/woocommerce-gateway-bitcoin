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
	edit: Edit,
	save: ( { attributes } ) => {
		const { showLabel } = attributes;

		return (
			<>
				<span
					className="bh-wp-bitcoin-gateway-payment-amount-received"
					data-attribute-showLabel={ showLabel }
				/>
			</>
		);
	},
} );
