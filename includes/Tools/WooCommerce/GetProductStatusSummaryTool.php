<?php

namespace WP_MCP_Server\Tools\WooCommerce;

use WP_MCP_Server\Services\WooReportService;
use WP_MCP_Server\Tools\Contracts\ToolInterface;
use WP_MCP_Server\Tools\ToolResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GetProductStatusSummaryTool implements ToolInterface {

	public function name(): string {
		return 'wc_get_product_status_summary';
	}

	public function description(): string {
		return 'Gets a summary count of WooCommerce products and variations by status.';
	}

	public function input_schema(): array {
		return [
			'type'       => 'object',
			'properties' => new \stdClass(),
		];
	}

	public function required_scopes(): array {
		return [ 'woocommerce:read' ];
	}

	public function execute( array $arguments = [] ): array {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return ToolResponse::error( 'WooCommerce is not active.' );
		}

		return ToolResponse::json(
			( new WooReportService() )->product_status_summary()
		);
	}

	public function annotations(): array {
		return [
			'title'           => 'Get WC product status summary',
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false,
		];
	}

	public function output_schema(): ?array {
		return [
			'type'       => 'object',
			'required'   => [ 'products', 'variations', 'total' ],
			'properties' => [
				'products' => [
					'type'                 => 'object',
					'additionalProperties' => [ 'type' => 'integer' ],
				],
				'variations' => [
					'type'                 => 'object',
					'additionalProperties' => [ 'type' => 'integer' ],
				],
				'total' => [
					'type' => 'integer',
				],
			],
		];
	}
}