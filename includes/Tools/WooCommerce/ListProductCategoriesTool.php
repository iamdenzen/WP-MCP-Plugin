<?php

namespace WP_MCP_Server\Tools\WooCommerce;

use WP_MCP_Server\Tools\Contracts\ToolInterface;
use WP_MCP_Server\Tools\ToolResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ListProductCategoriesTool implements ToolInterface {

	public function name(): string {
		return 'wc_list_product_categories';
	}

	public function description(): string {
		return 'Lists WooCommerce product categories with safe read-only metadata.';
	}

	public function input_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'hide_empty' => [
					'type'        => 'boolean',
					'description' => 'Whether to hide categories with no products. Default: false.',
				],
				'parent' => [
					'type'        => 'integer',
					'description' => 'Filter categories by parent term ID. Use 0 for top-level categories.',
				],
				'limit' => [
					'type'        => 'integer',
					'description' => 'Maximum number of categories to return. Default: 100. Max: 250.',
				],
			],
		];
	}

	public function required_scopes(): array {
		return [ 'woocommerce:read' ];
	}

	public function execute( array $arguments = [] ): array {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return ToolResponse::error( 'WooCommerce is not active.' );
		}

		$hide_empty = isset( $arguments['hide_empty'] )
			? (bool) $arguments['hide_empty']
			: false;

		$limit = isset( $arguments['limit'] )
			? absint( $arguments['limit'] )
			: 100;

		$limit = max( 1, min( 250, $limit ) );

		$query = [
			'taxonomy'   => 'product_cat',
			'hide_empty' => $hide_empty,
			'number'     => $limit,
			'orderby'    => 'name',
			'order'      => 'ASC',
		];

		if ( isset( $arguments['parent'] ) ) {
			$query['parent'] = absint( $arguments['parent'] );
		}

		$terms = get_terms( $query );

		if ( is_wp_error( $terms ) ) {
			return ToolResponse::error( $terms->get_error_message() );
		}

		$categories = [];

		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$thumbnail_id = absint( get_term_meta( $term->term_id, 'thumbnail_id', true ) );

			$categories[] = [
				'id'          => (int) $term->term_id,
				'name'        => $term->name,
				'slug'        => $term->slug,
				'description' => wp_strip_all_tags( $term->description ),
				'parent'      => (int) $term->parent,
				'count'       => (int) $term->count,
				'url'         => get_term_link( $term ),
				'image'       => $thumbnail_id
					? [
						'id'  => $thumbnail_id,
						'url' => wp_get_attachment_image_url( $thumbnail_id, 'full' ) ?: null,
						'alt' => get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ),
					]
					: null,
			];
		}

		return ToolResponse::json(
			[
				'query' => [
					'hide_empty' => $hide_empty,
					'parent'     => isset( $arguments['parent'] ) ? absint( $arguments['parent'] ) : null,
					'limit'      => $limit,
				],
				'count'      => count( $categories ),
				'categories' => $categories,
			]
		);
	}
	
	public function annotations(): array {
		return [
			'title'           => 'List WC Product Categories',
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false,
		];
	}

	public function output_schema(): ?array {
		return [
			'type'       => 'object',
			'required'   => [ 'query', 'count', 'categories' ],
			'properties' => [
				'query'      => [
					'type'       => 'object',
					'required'   => [ 'hide_empty', 'parent', 'limit' ],
					'properties' => [
						'hide_empty' => [ 'type' => 'boolean' ],
						'parent'     => [ 'type' => [ 'integer', 'null' ] ],
						'limit'      => [ 'type' => 'integer' ],
					],
				],

				'count'      => [ 'type' => 'integer' ],

				'categories' => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'required'   => [
							'id',
							'name',
							'slug',
							'description',
							'parent',
							'count',
							'url',
							'image',
						],
						'properties' => [
							'id'          => [ 'type' => 'integer' ],
							'name'        => [ 'type' => 'string' ],
							'slug'        => [ 'type' => 'string' ],
							'description' => [ 'type' => 'string' ],
							'parent'      => [ 'type' => 'integer' ],
							'count'       => [ 'type' => 'integer' ],
							'url'         => [ 'type' => [ 'string', 'null' ] ],
							'image'       => [
								'type'       => [ 'object', 'null' ],
								'required'   => [ 'id', 'url', 'alt' ],
								'properties' => [
									'id'  => [ 'type' => 'integer' ],
									'url' => [ 'type' => [ 'string', 'null' ] ],
									'alt' => [ 'type' => 'string' ],
								],
							],
						],
					],
				],
			],
		];
	}
}