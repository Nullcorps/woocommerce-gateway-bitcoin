<?php
/**
 * A class to modify a block template by inserting a new block
 *
 * TODO: This needs a better definition of the path to allow inserting after a specific block rather than the first found.
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce;

use WP_Block_Template;

/**
 * @see get_block_templates
 *
 * TemplatePath is currently a simple array of block names to traverse, e.g. ["core/group","woocommerce/order-confirmation-summary"], but will change in future as needed.
 * @phpstan-type TemplatePath array<string>
 * For `innerBlocks`, "Circular definition detected in type alias BlockArray."
 * @phpstan-type BlockArray array{blockName:string,attrs:array{tagName:string,layout:array{inherit:bool,type:string}},innerBlocks:array,innerHTML?:string,innerContent?:array<string>}
 */
class Modify_Template {

	/**
	 * E.g. 'order-confirmation'
	 */
	protected string $template_slug;
	/**
	 * E.g. ["core/group","woocommerce/order-confirmation-summary"]
	 *
	 * @var TemplatePath $path
	 */
	protected array $path;
	/** @var BlockArray $new_block */
	protected array $new_block;

	/**
	 * @param string       $template_slug
	 * @param TemplatePath $path
	 * @param BlockArray   $new_block
	 */
	public function __construct(
		string $template_slug,
		array $path,
		array $new_block
	) {
		$this->template_slug = $template_slug;
		$this->path          = $path;
		$this->new_block     = $new_block;

		add_filter( 'pre_get_block_templates', array( $this, 'register_filter_for_query' ), 100, 3 );
	}

	/**
	 * Only hook the filter when the template slug is in the query.
	 *
	 * @see get_block_templates
	 * @hooked pre_get_block_templates
	 *
	 * @param ?WP_Block_Template[]          $result This is null when called by the filter but another plugin may return an array of templates to short-circuit the query.
	 * @param array{slug__in:array<string>} $query
	 * @param string                        $template_type wp_template|wp_template_part
	 *
	 * @return ?WP_Block_Template[]
	 */
	public function register_filter_for_query( ?array $result, array $query, string $template_type ): ?array {

		if ( ! is_null( $result ) ) {
			/**
			 * Another filter has already modified the result, which will short-circuit the query and
			 * `get_block_templates` will not run anyway.
			 */
			return $result;
		}

		// $result will be null if this filter has not been used to short-circuit the query.
		if ( in_array( $this->template_slug, $query['slug__in'] ?? array(), true ) ) {
			add_filter( 'get_block_templates', array( $this, 'modify_template' ), 100, 3 );
		}

		return $result; // Which is `null` here.
	}

	/**
	 *
	 * @hooked get_block_templates
	 * @see get_block_templates
	 *
	 * @param WP_Block_Template[]                                  $query_result
	 * @param array{slug__in: string[]|null, slug__not_in: string} $query
	 * @param string                                               $template_type
	 *
	 * @return WP_Block_Template[]
	 */
	public function modify_template( array $query_result, array $query, string $template_type ): array {

		/** @var WP_Block_Template|null $block_template */
		$block_template = array_reduce(
			$query_result,
			fn( $carry, $item ) => $item->slug === $this->template_slug ? $item : $carry,
			null
		);

		if ( ! is_null( $block_template ) ) {
			/**
			 * Since objects are passed by reference, modifications elsewhere will persist.
			 */
			$this->insert_new_block( $block_template );
		}

		remove_filter( 'get_block_templates', array( $this, 'modify_template' ), 100 );

		return $query_result;
	}

	/**
	 * Parse the comment-serialized blocks, insert the new block, re-serialize the blocks and set the WP_Block_Template::$content.
	 */
	protected function insert_new_block( WP_Block_Template $block_template ): void {

		$blocks = parse_blocks( $block_template->content );

		$new_blocks = $this->search_for_path_and_insert( $blocks, $this->path, $this->new_block );

		$new_blocks_template_string = traverse_and_serialize_blocks( $new_blocks );

		$block_template->content = $new_blocks_template_string;
	}

	/**
	 * Recursively loop over array of blocks until we find the path, then insert.
	 *
	 * @param BlockArray[] $blocks
	 * @param TemplatePath $path
	 * @param BlockArray   $new_block
	 *
	 * @return BlockArray[]
	 */
	protected function search_for_path_and_insert( array $blocks, array $path, array $new_block ): array {
		$new_blocks = array();

		$seeking_path = array_shift( $path );

		foreach ( $blocks as $block ) {
			if ( $block['blockName'] === $seeking_path ) {
				if ( empty( $path ) ) {
					$new_blocks[] = $block;
					$new_blocks[] = $new_block;
				} else {
					$block['innerBlocks'] = $this->search_for_path_and_insert( $block['innerBlocks'], $path, $new_block );
					$new_blocks[]         = $block;
				}
			} else {
				$new_blocks[] = $block;
			}
		}

		return $new_blocks;
	}
}
