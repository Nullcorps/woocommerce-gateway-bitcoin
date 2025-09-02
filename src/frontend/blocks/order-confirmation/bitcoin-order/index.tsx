/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { Edit } from './edit';
import { Save } from './save';

registerBlockType(metadata.name, {
	...metadata,
	edit: Edit,
	// save: Save,


  save: () => {
    const blockProps = useBlockProps.save();

    return (
      <div { ...blockProps }>
        <InnerBlocks.Content />
      </div>
    );
  },
});