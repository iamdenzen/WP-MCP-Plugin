<?php

namespace WP_MCP_Server\Tools\WooCommerce;

use WP_MCP_Server\Services\WooReportService;
use WP_MCP_Server\Tools\Contracts\ToolInterface;
use WP_MCP_Server\Tools\ToolResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GetOrderReportTool implements ToolInterface {

	public function name(): string {
		return 'wc_get_order_report';
	}

	public function description(): string {
		return 'Gets WooCommerce order sales report data grouped by day or month.';
	}

	public function input_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'date_from' => [
					'type'        => 'string',
					'description' => 'Start date in YYYY-MM-DD format. Default: first day of current month.',
				],
				'date_to' => [
					'type'        => 'string',
					'description' => 'End date in YYYY-MM-DD format. Default: today.',
				],
				'group_by' => [
					'type'        => 'string',
					'description' => 'Group report by day or month. Default: month.',
					'enum'        => [ 'day', 'month' ],
				],
				'statuses' => [
					'type'        => 'array',
					'description' => 'Optional WooCommerce order statuses, for example wc-completed or wc-processing.',
					'items'       => [
						'type' => 'string',
					],
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

		$data = ( new WooReportService() )->order_report( $arguments );

		if ( isset( $data['error'] ) ) {
			return ToolResponse::error( (string) $data['error'] );
		}

		return ToolResponse::json( $data );
	}

	public function annotations(): array {
		return [
			'title'           => 'Get WC order report',
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false,
		];
	}

	public function output_schema(): ?array {
		return [
			'type'       => 'object',
			'required'   => [ 'period', 'totals', 'rows' ],
			'properties' => [
				'period' => [
					'type'       => 'object',
					'required'   => [ 'from', 'to', 'group_by', 'statuses' ],
					'properties' => [
						'from'     => [ 'type' => 'string' ],
						'to'       => [ 'type' => 'string' ],
						'group_by' => [ 'type' => 'string' ],
						'statuses' => [
							'type'  => 'array',
							'items' => [ 'type' => 'string' ],
						],
					],
				],
				'totals' => self::report_totals_schema(),
				'rows'   => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'required'   => [
							'period',
							'orders',
							'items_sold',
							'gross_sales',
							'net_sales',
							'tax',
							'shipping',
						],
						'properties' => array_merge(
							[
								'period' => [ 'type' => 'string' ],
							],
							self::report_metric_properties()
						),
					],
				],
			],
		];
	}

	private static function report_totals_schema(): array {
		return [
			'type'       => 'object',
			'required'   => [
				'orders',
				'items_sold',
				'gross_sales',
				'net_sales',
				'tax',
				'shipping',
				'average_order_value',
			],
			'properties' => array_merge(
				self::report_metric_properties(),
				[
					'average_order_value' => [ 'type' => 'number' ],
				]
			),
		];
	}

	private static function report_metric_properties(): array {
		return [
			'orders'      => [ 'type' => 'integer' ],
			'items_sold'  => [ 'type' => 'integer' ],
			'gross_sales' => [ 'type' => 'number' ],
			'net_sales'   => [ 'type' => 'number' ],
			'tax'         => [ 'type' => 'number' ],
			'shipping'    => [ 'type' => 'number' ],
		];
	}
}