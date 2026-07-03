<?php

namespace WP_MCP_Server\Tools\WooCommerce;

use WP_MCP_Server\Services\WooProductFormatter;
use WP_MCP_Server\Services\WooProductResolver;
use WP_MCP_Server\Tools\Contracts\ToolInterface;
use WP_MCP_Server\Tools\ToolResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GetProductTool implements ToolInterface {

	public function name(): string {
		return 'wc_get_product';
	}

	public function description(): string {
		return 'Gets full safe details for a published WooCommerce product by ID.';
	}

	public function input_schema(): array {
		return [
			'type'       => 'object',
			'properties' => WooProductResolver::input_schema_properties(),
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

		return ToolResponse::json(
			( new WooProductFormatter() )->full( $product )
		);
	}
	
	public function annotations(): array {
		return [
			'title'           => 'Get WC Product by ID or SKU',
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false,
		];
	}

	public function output_schema(): ?array {
		return null;
		//return WooProductFormatter::full_schema();
	}
}