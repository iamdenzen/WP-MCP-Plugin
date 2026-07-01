<?php

namespace WP_MCP_Server\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WooProductResolver {

	public static function resolve( array $arguments ): ?\WC_Product {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return null;
		}

		$product_id = self::resolve_id( $arguments );

		if ( ! $product_id ) {
			return null;
		}

		$product = wc_get_product( $product_id );

		return $product instanceof \WC_Product ? $product : null;
	}

	public static function resolve_published( array $arguments ): ?\WC_Product {
		$product = self::resolve( $arguments );

		if ( ! $product instanceof \WC_Product ) {
			return null;
		}

		if ( 'publish' !== $product->get_status() ) {
			return null;
		}

		return $product;
	}

	public static function resolve_id( array $arguments ): int {
		if ( ! empty( $arguments['id'] ) ) {
			return absint( $arguments['id'] );
		}

		if ( ! empty( $arguments['sku'] ) && function_exists( 'wc_get_product_id_by_sku' ) ) {
			return absint(
				wc_get_product_id_by_sku(
					sanitize_text_field( (string) $arguments['sku'] )
				)
			);
		}

		return 0;
	}

	public static function input_schema_properties(): array {
		return [
			'id' => [
				'type'        => 'integer',
				'description' => 'WooCommerce product ID.',
			],
			'sku' => [
				'type'        => 'string',
				'description' => 'WooCommerce product SKU.',
			],
		];
	}

	public static function missing_identifier_message(): string {
		return 'Either "id" or "sku" is required.';
	}
}