<?php

namespace WP_MCP_Server\Tools\WooCommerce;

use WP_MCP_Server\Services\WooProductResolver;
use WP_MCP_Server\Tools\Contracts\ToolInterface;
use WP_MCP_Server\Tools\ToolResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GetProductReviewsTool implements ToolInterface {

	public function name(): string {
		return 'wc_get_product_reviews';
	}

	public function description(): string {
		return 'Gets approved WooCommerce product reviews for a product by ID or SKU.';
	}

	public function input_schema(): array {
		return [
			'type'       => 'object',
			'properties' => array_merge(
				WooProductResolver::input_schema_properties(),
				[
					'limit' => [
						'type'        => 'integer',
						'description' => 'Maximum number of reviews to return. Default: 20. Max: 100.',
					],
				]
			),
		];
	}

	public function required_scopes(): array {
		return [ 'woocommerce:read' ];
	}

	public function execute( array $arguments = [] ): array {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return ToolResponse::error( 'WooCommerce is not active.' );
		}

		if ( empty( $arguments['id'] ) && empty( $arguments['sku'] ) ) {
			return ToolResponse::error( WooProductResolver::missing_identifier_message() );
		}

		$product = WooProductResolver::resolve_published( $arguments );

		if ( ! $product instanceof \WC_Product ) {
			return ToolResponse::error( 'Product not found.' );
		}

		$limit = isset( $arguments['limit'] ) ? absint( $arguments['limit'] ) : 20;
		$limit = max( 1, min( 100, $limit ) );

		$comments = get_comments(
			[
				'post_id' => $product->get_id(),
				'status'  => 'approve',
				'type'    => 'review',
				'number'  => $limit,
				'orderby' => 'comment_date_gmt',
				'order'   => 'DESC',
			]
		);

		$reviews = [];

		foreach ( $comments as $comment ) {
			if ( ! $comment instanceof \WP_Comment ) {
				continue;
			}

			$reviews[] = [
				'id'            => (int) $comment->comment_ID,
				'rating'        => (int) get_comment_meta( $comment->comment_ID, 'rating', true ),
				'review'        => wp_strip_all_tags( $comment->comment_content ),
				'reviewer_name' => $comment->comment_author,
				'date'          => get_comment_date( DATE_ATOM, $comment ),
				'verified'      => (bool) get_comment_meta( $comment->comment_ID, 'verified', true ),
			];
		}

		return ToolResponse::json(
			[
				'product' => [
					'id'   => $product->get_id(),
					'name' => $product->get_name(),
					'sku'  => $product->get_sku(),
				],
				'count'   => count( $reviews ),
				'reviews' => $reviews,
			]
		);
	}
	
	public function annotations(): array {
		return [
			'title'           => 'Get WC Product Reviews',
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false,
		];
	}

	public function output_schema(): ?array {
		return [
			'type'       => 'object',
			'required'   => [ 'product', 'count', 'reviews' ],
			'properties' => [
				'product' => [
					'type'       => 'object',
					'required'   => [ 'id', 'name', 'sku' ],
					'properties' => [
						'id'   => [ 'type' => 'integer' ],
						'name' => [ 'type' => 'string' ],
						'sku'  => [ 'type' => 'string' ],
					],
				],

				'count'   => [ 'type' => 'integer' ],

				'reviews' => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'required'   => [
							'id',
							'rating',
							'review',
							'reviewer_name',
							'date',
							'verified',
						],
						'properties' => [
							'id'            => [ 'type' => 'integer' ],
							'rating'        => [ 'type' => 'integer' ],
							'review'        => [ 'type' => 'string' ],
							'reviewer_name' => [ 'type' => 'string' ],
							'date'          => [ 'type' => 'string', 'format' => 'date-time' ],
							'verified'      => [ 'type' => 'boolean' ],
						],
					],
				],
			],
		];
	}
}