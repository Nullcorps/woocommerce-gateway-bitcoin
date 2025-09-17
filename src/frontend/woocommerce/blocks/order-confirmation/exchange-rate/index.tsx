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
		const { showLabel, useUrl } = attributes;

		return (
			<>
				<span
					className="bh-wp-bitcoin-gateway-exchange-rate"
					data-attribute-showLabel={ showLabel }
					data-attribute-useUrl={ useUrl }
				/>
			</>
		);
	},
} );
