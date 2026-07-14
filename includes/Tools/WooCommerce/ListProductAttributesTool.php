<?php

namespace WP_MCP_Server\Tools\WooCommerce;

use WP_MCP_Server\Tools\Contracts\ToolInterface;
use WP_MCP_Server\Tools\ToolResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ListProductAttributesTool implements ToolInterface {

	/**
	 * Hard ceiling on terms returned per attribute, regardless of caller input.
	 */
	private const MAX_TERMS_LIMIT = 250;

	/**
	 * Hard ceiling on how many terms we will ever pull per taxonomy from the
	 * database before slicing for pagination. Protects against unbounded
	 * memory/query cost on stores with huge attribute vocabularies (e.g. a
	 * "SKU" attribute with 50k terms).
	 */
	private const MAX_TERMS_FETCH_PER_TAXONOMY = 2000;

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
				'include_terms'    => [
					'type'        => 'boolean',
					'description' => 'Whether to include attribute terms. Default: true.',
				],
				'terms_limit'      => [
					'type'        => 'integer',
					'description' => 'Maximum number of terms per attribute. Default: 100. Max: 250.',
				],
				'terms_offset'     => [
					'type'        => 'integer',
					'description' => 'Number of terms to skip per attribute, for pagination. Default: 0.',
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

		$include_terms    = $this->to_bool( $arguments['include_terms'] ?? true );
		$hide_empty_terms = $this->to_bool( $arguments['hide_empty_terms'] ?? false );

		$terms_limit = isset( $arguments['terms_limit'] ) ? absint( $arguments['terms_limit'] ) : 100;
		$terms_limit = max( 1, min( self::MAX_TERMS_LIMIT, $terms_limit ) );

		$terms_offset = isset( $arguments['terms_offset'] ) ? absint( $arguments['terms_offset'] ) : 0;

		$attribute_taxonomies = wc_get_attribute_taxonomies();

		if ( ! is_array( $attribute_taxonomies ) ) {
			$attribute_taxonomies = [];
		}

		// Deterministic ordering, independent of DB insertion order.
		usort(
			$attribute_taxonomies,
			static function ( $a, $b ): int {
				return strcasecmp( (string) $a->attribute_label, (string) $b->attribute_label );
			}
		);

		$taxonomy_map = [];
		foreach ( $attribute_taxonomies as $attribute ) {
			$taxonomy = wc_attribute_taxonomy_name( $attribute->attribute_name );
			$taxonomy_map[ $taxonomy ] = $attribute;
		}

		$terms_by_taxonomy = $include_terms
			? $this->fetch_terms_grouped_by_taxonomy( array_keys( $taxonomy_map ), $hide_empty_terms )
			: [];

		$attributes = [];

		foreach ( $taxonomy_map as $taxonomy => $attribute ) {
			$taxonomy_is_registered = taxonomy_exists( $taxonomy );

			$item = [
				'id'         => (int) $attribute->attribute_id,
				'name'       => $attribute->attribute_name,
				'label'      => $attribute->attribute_label,
				'taxonomy'   => $taxonomy,
				'type'       => $attribute->attribute_type,
				'order_by'   => $attribute->attribute_orderby,
				'public'     => (bool) $attribute->attribute_public,
				'admin_url'  => $taxonomy_is_registered
					? admin_url( 'edit-tags.php?taxonomy=' . rawurlencode( $taxonomy ) . '&post_type=product' )
					: null,
			];

			if ( $include_terms ) {
				$all_terms_for_taxonomy = $terms_by_taxonomy[ $taxonomy ] ?? [];
				$total_terms            = count( $all_terms_for_taxonomy );

				$page_of_terms = array_slice( $all_terms_for_taxonomy, $terms_offset, $terms_limit );

				$item['terms'] = array_map(
					static function ( \WP_Term $term ): array {
						return [
							'id'          => (int) $term->term_id,
							'name'        => $term->name,
							'slug'        => $term->slug,
							'description' => wp_strip_all_tags( (string) $term->description ),
							'count'       => (int) $term->count,
						];
					},
					$page_of_terms
				);

				$item['total_terms'] = $total_terms;
				$item['has_more']    = ( $terms_offset + count( $page_of_terms ) ) < $total_terms;
			}

			$attributes[] = $item;
		}

		return ToolResponse::json(
			[
				'query'      => [
					'include_terms'    => $include_terms,
					'terms_limit'      => $terms_limit,
					'terms_offset'     => $terms_offset,
					'hide_empty_terms' => $hide_empty_terms,
				],
				'count'      => count( $attributes ),
				'attributes' => $attributes,
			]
		);
	}

	/**
	 * Fetches terms for many taxonomies in a single query and groups the
	 * result by taxonomy, avoiding an N+1 query per attribute.
	 *
	 * @param string[] $taxonomies
	 * @param bool     $hide_empty
	 * @return array<string, \WP_Term[]> Keyed by taxonomy, terms sorted by name.
	 */
	private function fetch_terms_grouped_by_taxonomy( array $taxonomies, bool $hide_empty ): array {
		if ( empty( $taxonomies ) ) {
			return [];
		}

		// Only query taxonomies that are actually registered; get_terms()
		// errors out on unregistered taxonomies.
		$registered_taxonomies = array_values( array_filter( $taxonomies, 'taxonomy_exists' ) );

		if ( empty( $registered_taxonomies ) ) {
			return [];
		}

		$terms = get_terms(
			[
				'taxonomy'   => $registered_taxonomies,
				'hide_empty' => $hide_empty,
				'orderby'    => 'name',
				'order'      => 'ASC',
				// Capped rather than unlimited, to bound memory/query cost;
				// per-attribute pagination happens afterward in PHP.
				'number'     => self::MAX_TERMS_FETCH_PER_TAXONOMY * count( $registered_taxonomies ),
			]
		);

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return [];
		}

		$grouped = [];
		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}
			$grouped[ $term->taxonomy ][] = $term;
		}

		// Enforce the per-taxonomy cap defensively in case the combined
		// query above returned an uneven distribution across taxonomies.
		foreach ( $grouped as $taxonomy => $taxonomy_terms ) {
			if ( count( $taxonomy_terms ) > self::MAX_TERMS_FETCH_PER_TAXONOMY ) {
				$grouped[ $taxonomy ] = array_slice( $taxonomy_terms, 0, self::MAX_TERMS_FETCH_PER_TAXONOMY );
			}
		}

		return $grouped;
	}

	/**
	 * Coerces loosely-typed MCP input (bool, "true"/"false", 1/0, "1"/"0")
	 * into a strict boolean, since not all MCP clients send native JSON booleans.
	 */
	private function to_bool( $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
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
					'required'   => [ 'include_terms', 'terms_limit', 'terms_offset', 'hide_empty_terms' ],
					'properties' => [
						'include_terms'    => [ 'type' => 'boolean' ],
						'terms_limit'      => [ 'type' => 'integer' ],
						'terms_offset'     => [ 'type' => 'integer' ],
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
							'admin_url',
						],
						'properties' => [
							'id'          => [ 'type' => 'integer' ],
							'name'        => [ 'type' => 'string' ],
							'label'       => [ 'type' => 'string' ],
							'taxonomy'    => [ 'type' => 'string' ],
							'type'        => [ 'type' => 'string' ],
							'order_by'    => [ 'type' => 'string' ],
							'public'      => [ 'type' => 'boolean' ],
							'admin_url'   => [ 'type' => [ 'string', 'null' ] ],
							'total_terms' => [ 'type' => 'integer' ],
							'has_more'    => [ 'type' => 'boolean' ],

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