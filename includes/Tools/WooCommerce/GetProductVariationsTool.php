<?php

namespace WP_MCP_Server\Tools\WooCommerce;

use WP_MCP_Server\Services\WooProductResolver;
use WP_MCP_Server\Tools\Contracts\ToolInterface;
use WP_MCP_Server\Tools\ToolResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GetProductVariationsTool implements ToolInterface {

	public function name(): string {
		return 'wc_get_product_variations';
	}

	public function description(): string {
		return 'Gets variation details for a published WooCommerce variable product by ID or SKU.';
	}

	public function input_schema(): array {
		return [
			'type'       => 'object',
			'properties' => array_merge(
				WooProductResolver::input_schema_properties(),
				[
					'limit' => [
						'type'        => 'integer',
						'description' => 'Maximum number of variations to return. Default: 50. Max: 250.',
					],
				]
			),
		];
	}

	public function required_scopes(): array {
		return [ 'woocommerce:read' ];
	}

	public function execute( array $arguments = [] ): array {
		if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_product' ) ) {
			return ToolResponse::error( 'WooCommerce is not active.' );
		}

		if ( empty( $arguments['id'] ) && empty( $arguments['sku'] ) ) {
			return ToolResponse::error( WooProductResolver::missing_identifier_message() );
		}

		$product = WooProductResolver::resolve_published( $arguments );

		if ( ! $product instanceof \WC_Product ) {
			return ToolResponse::error( 'Product not found.' );
		}

		if ( ! $product->is_type( 'variable' ) ) {
			return ToolResponse::error( 'Product is not a variable product.' );
		}

		$limit = isset( $arguments['limit'] ) ? absint( $arguments['limit'] ) : 50;
		$limit = max( 1, min( 250, $limit ) );

		$variation_ids = array_slice(
			array_map( 'absint', $product->get_children() ),
			0,
			$limit
		);

		$variations = [];

		foreach ( $variation_ids as $variation_id ) {
			$variation = wc_get_product( $variation_id );

			if ( ! $variation instanceof \WC_Product_Variation ) {
				continue;
			}

			if ( 'publish' !== $variation->get_status() ) {
				continue;
			}

			$variations[] = [
				'id'             => $variation->get_id(),
				'sku'            => $variation->get_sku(),
				'name'           => $variation->get_name(),
				'url'            => get_permalink( $variation->get_id() ),
				'description'    => wp_strip_all_tags( $variation->get_description() ),
				'attributes'     => $variation->get_attributes(),
				'price'          => $variation->get_price(),
				'regular_price'  => $variation->get_regular_price(),
				'sale_price'     => $variation->get_sale_price(),
				'on_sale'        => $variation->is_on_sale(),
				'stock_status'   => $variation->get_stock_status(),
				'manage_stock'   => $variation->get_manage_stock(),
				'stock_quantity' => $variation->get_stock_quantity(),
				'backorders'     => $variation->get_backorders(),
				'weight'         => $variation->get_weight(),
				'length'         => $variation->get_length(),
				'width'          => $variation->get_width(),
				'height'         => $variation->get_height(),
				'image'          => $variation->get_image_id()
					? [
						'id'  => $variation->get_image_id(),
						'url' => wp_get_attachment_image_url( $variation->get_image_id(), 'full' ) ?: null,
						'alt' => get_post_meta( $variation->get_image_id(), '_wp_attachment_image_alt', true ),
					]
					: null,
			];
		}

		return ToolResponse::json(
			[
				'product' => [
					'id'   => $product->get_id(),
					'name' => $product->get_name(),
					'sku'  => $product->get_sku(),
					'type' => $product->get_type(),
				],
				'query' => [
					'limit' => $limit,
				],
				'count'      => count( $variations ),
				'variations' => $variations,
			]
		);
	}
	
	public function annotations(): array {
		return [
			'title'           => 'Get WC Product Variations',
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false,
		];
	}

	public function output_schema(): ?array {
		return [
			'type'       => 'object',
			'required'   => [ 'product', 'query', 'count', 'variations' ],
			'properties' => [
				'product'    => [
					'type'       => 'object',
					'required'   => [ 'id', 'name', 'sku', 'type' ],
					'properties' => [
						'id'   => [ 'type' => 'integer' ],
						'name' => [ 'type' => 'string' ],
						'sku'  => [ 'type' => 'string' ],
						'type' => [ 'type' => 'string' ],
					],
				],

				'query'      => [
					'type'       => 'object',
					'required'   => [ 'limit' ],
					'properties' => [
						'limit' => [ 'type' => 'integer' ],
					],
				],

				'count'      => [ 'type' => 'integer' ],

				'variations' => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'required'   => [
							'id',
							'sku',
							'name',
							'url',
							'description',
							'attributes',
							'price',
							'regular_price',
							'sale_price',
							'on_sale',
							'stock_status',
							'manage_stock',
							'stock_quantity',
							'backorders',
							'weight',
							'length',
							'width',
							'height',
							'image',
						],
						'properties' => [
							'id'             => [ 'type' => 'integer' ],
							'sku'            => [ 'type' => 'string' ],
							'name'           => [ 'type' => 'string' ],
							'url'            => [ 'type' => [ 'string', 'null' ] ],
							'description'    => [ 'type' => 'string' ],

							'attributes'     => [
								'type'                 => 'object',
								'additionalProperties' => [
									'type' => 'string',
								],
							],

							'price'          => [ 'type' => 'string' ],
							'regular_price'  => [ 'type' => 'string' ],
							'sale_price'     => [ 'type' => 'string' ],
							'on_sale'        => [ 'type' => 'boolean' ],

							'stock_status'   => [ 'type' => 'string' ],
							'manage_stock'   => [ 'type' => 'boolean' ],
							'stock_quantity' => [ 'type' => [ 'integer', 'null' ] ],
							'backorders'     => [ 'type' => 'string' ],

							'weight'         => [ 'type' => 'string' ],
							'length'         => [ 'type' => 'string' ],
							'width'          => [ 'type' => 'string' ],
							'height'         => [ 'type' => 'string' ],

							'image'          => [
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