<?php

namespace WP_MCP_Server\Tools\WooCommerce;

use WP_MCP_Server\Tools\Contracts\ToolInterface;
use WP_MCP_Server\Tools\ToolResponse;
use WP_MCP_Server\Services\WooProductFormatter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SearchProductsTool implements ToolInterface {

	public function name(): string {
		return 'wc_search_products';
	}

	public function description(): string {
		return 'Searches published WooCommerce products by keyword.';
	}

	public function input_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'search' => [
					'type'        => 'string',
					'description' => 'Search keyword.',
				],
				'limit' => [
					'type'        => 'integer',
					'description' => 'Maximum number of products to return. Default: 10. Max: 50.',
				],
			],
		];
	}

	public function required_scopes(): array {
		return [ 'woocommerce:read' ];
	}

	public function execute( array $arguments = [] ): array {
		if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_products' ) ) {
			return ToolResponse::error( 'WooCommerce is not active.' );
		}

		$search = isset( $arguments['search'] )
			? sanitize_text_field( $arguments['search'] )
			: '';

		$limit = isset( $arguments['limit'] )
			? absint( $arguments['limit'] )
			: 10;

		$limit = max( 1, min( 50, $limit ) );

		$products = wc_get_products(
			[
				'status' => 'publish',
				'limit'  => $limit,
				'search' => $search,
				'return' => 'objects',
			]
		);

		$items = [];

		$formatter = new WooProductFormatter();

		foreach ( $products as $product ) {
			if ( ! $product instanceof \WC_Product ) {
				continue;
			}

			$items[] = $formatter->summary( $product );
		}

		return ToolResponse::json(
			[
				'query'    => [
					'search' => $search,
					'limit'  => $limit,
				],
				'count'    => count( $items ),
				'products' => $items,
			]
		);
	}
	
	public function annotations(): array {
		return [
			'title'           => 'Search WC products',
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false,
		];
	}

	public function output_schema(): ?array {
		return [
			'type'       => 'object',
			'required'   => [ 'query', 'count', 'products' ],
			'properties' => [
				'query' => [
					'type' => 'object',
					'properties' => [
						'search' => [ 'type' => 'string' ],
						'limit'  => [ 'type' => 'integer' ],
					],
				],
				'count' => [
					'type' => 'integer',
				],
				'products' => [
					'type'  => 'array',
					'items' => WooProductFormatter::summary_schema(),
				],
			],
		];
	}
}