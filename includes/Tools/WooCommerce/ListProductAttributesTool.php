<?php

namespace WP_MCP_Server\Tools\WooCommerce;

use WP_MCP_Server\Tools\Contracts\ToolInterface;
use WP_MCP_Server\Tools\ToolResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ListProductAttributesTool implements ToolInterface {

	public function name(): string {
		return 'wc_list_product_attributes';
	}

	public function description(): string {
		return 'Lists WooCommerce global product attributes and optionally their terms.';
	}

	public function input_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'include_terms' => [
					'type'        => 'boolean',
					'description' => 'Whether to include attribute terms. Default: true.',
				],
				'terms_limit' => [
					'type'        => 'integer',
					'description' => 'Maximum number of terms per attribute. Default: 100. Max: 250.',
				],
				'hide_empty_terms' => [
					'type'        => 'boolean',
					'description' => 'Whether to hide unused terms. Default: false.',
				],
			],
		];
	}

	public function required_scopes(): array {
		return [ 'woocommerce:read' ];
	}

	public function execute( array $arguments = [] ): array {
		if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
			return ToolResponse::error( 'WooCommerce is not active.' );
		}

		$include_terms = isset( $arguments['include_terms'] )
			? (bool) $arguments['include_terms']
			: true;

		$terms_limit = isset( $arguments['terms_limit'] )
			? absint( $arguments['terms_limit'] )
			: 100;

		$terms_limit = max( 1, min( 250, $terms_limit ) );

		$hide_empty_terms = isset( $arguments['hide_empty_terms'] )
			? (bool) $arguments['hide_empty_terms']
			: false;

		$attribute_taxonomies = wc_get_attribute_taxonomies();
		$attributes           = [];

		foreach ( $attribute_taxonomies as $attribute ) {
			$taxonomy = wc_attribute_taxonomy_name( $attribute->attribute_name );

			$item = [
				'id'           => (int) $attribute->attribute_id,
				'name'         => $attribute->attribute_name,
				'label'        => $attribute->attribute_label,
				'taxonomy'     => $taxonomy,
				'type'         => $attribute->attribute_type,
				'order_by'     => $attribute->attribute_orderby,
				'public'       => (bool) $attribute->attribute_public,
				'archive_url'  => taxonomy_exists( $taxonomy ) && $attribute->attribute_public
					? get_term_link( '', $taxonomy )
					: null,
			];

			if ( $include_terms && taxonomy_exists( $taxonomy ) ) {
				$terms = get_terms(
					[
						'taxonomy'   => $taxonomy,
						'hide_empty' => $hide_empty_terms,
						'number'     => $terms_limit,
						'orderby'    => 'name',
						'order'      => 'ASC',
					]
				);

				$item['terms'] = is_wp_error( $terms )
					? []
					: array_map(
						static function ( \WP_Term $term ): array {
							return [
								'id'          => (int) $term->term_id,
								'name'        => $term->name,
								'slug'        => $term->slug,
								'description' => wp_strip_all_tags( $term->description ),
								'count'       => (int) $term->count,
							];
						},
						$terms
					);
			}

			$attributes[] = $item;
		}

		return ToolResponse::json(
			[
				'query' => [
					'include_terms'    => $include_terms,
					'terms_limit'      => $terms_limit,
					'hide_empty_terms' => $hide_empty_terms,
				],
				'count'      => count( $attributes ),
				'attributes' => $attributes,
			]
		);
	}
	
	public function annotations(): array {
		return [
			'title'           => 'List WC Product Attributes',
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false,
		];
	}

	public function output_schema(): ?array {
		return [
			'type'       => 'object',
			'required'   => [ 'query', 'count', 'attributes' ],
			'properties' => [
				'query'      => [
					'type'       => 'object',
					'required'   => [ 'include_terms', 'terms_limit', 'hide_empty_terms' ],
					'properties' => [
						'include_terms'    => [ 'type' => 'boolean' ],
						'terms_limit'      => [ 'type' => 'integer' ],
						'hide_empty_terms' => [ 'type' => 'boolean' ],
					],
				],

				'count'      => [ 'type' => 'integer' ],

				'attributes' => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'required'   => [
							'id',
							'name',
							'label',
							'taxonomy',
							'type',
							'order_by',
							'public',
							'archive_url',
						],
						'properties' => [
							'id'          => [ 'type' => 'integer' ],
							'name'        => [ 'type' => 'string' ],
							'label'       => [ 'type' => 'string' ],
							'taxonomy'    => [ 'type' => 'string' ],
							'type'        => [ 'type' => 'string' ],
							'order_by'    => [ 'type' => 'string' ],
							'public'      => [ 'type' => 'boolean' ],
							'archive_url' => [ 'type' => [ 'string', 'null' ] ],

							'terms'       => [
								'type'  => 'array',
								'items' => [
									'type'       => 'object',
									'required'   => [
										'id',
										'name',
										'slug',
										'description',
										'count',
									],
									'properties' => [
										'id'          => [ 'type' => 'integer' ],
										'name'        => [ 'type' => 'string' ],
										'slug'        => [ 'type' => 'string' ],
										'description' => [ 'type' => 'string' ],
										'count'       => [ 'type' => 'integer' ],
									],
								],
							],
						],
					],
				],
			],
		];
	}
}