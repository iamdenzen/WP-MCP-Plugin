<?php

namespace WP_MCP_Server\Tools\WooCommerce;

use WP_MCP_Server\Tools\Contracts\ToolInterface;
use WP_MCP_Server\Tools\ToolResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SearchOrdersTool implements ToolInterface {

	public function name(): string {
		return 'wc_search_orders';
	}

	public function description(): string {
		return 'Searches WooCommerce orders and returns safe read-only order summaries.';
	}

	public function input_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'status' => [
					'type'        => 'string',
					'description' => 'Optional order status. Example: processing, completed, pending, on-hold, cancelled, refunded, failed.',
				],
				'limit'  => [
					'type'        => 'integer',
					'description' => 'Maximum number of orders to return. Default 10, max 50.',
				],
				'page'   => [
					'type'        => 'integer',
					'description' => 'Page number. Default 1.',
				],
			],
		];
	}

	public function required_scopes(): array {
		return [ 'woocommerce:read' ];
	}

	public function execute( array $arguments = [] ): array {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return ToolResponse::error( 'WooCommerce orders are not available.' );
		}

		$limit = isset( $arguments['limit'] ) ? absint( $arguments['limit'] ) : 10;
		$limit = min( max( $limit, 1 ), 50 );

		$page = isset( $arguments['page'] ) ? absint( $arguments['page'] ) : 1;
		$page = max( $page, 1 );

		$args = [
			'limit'   => $limit,
			'page'    => $page,
			'orderby' => 'date',
			'order'   => 'DESC',
			'return'  => 'objects',
		];

		if ( ! empty( $arguments['status'] ) ) {
			$args['status'] = sanitize_key( $arguments['status'] );
		}

		$orders = wc_get_orders( $args );

		$data = array_map(
			[ $this, 'format_order_summary' ],
			$orders
		);

		return ToolResponse::json(
			[
				'orders' => $data,
				'limit'  => $limit,
				'page'   => $page,
			]
		);
	}

	private function format_order_summary( \WC_Order $order ): array {
		return [
			'id'                   => $order->get_id(),
			'number'               => $order->get_order_number(),
			'status'               => $order->get_status(),
			'date_created'         => $order->get_date_created() ? $order->get_date_created()->date( DATE_ATOM ) : null,
			'currency'             => $order->get_currency(),
			'total'                => $order->get_total(),
			'item_count'           => $order->get_item_count(),
			'payment_method'       => $order->get_payment_method(),
			'payment_method_title' => wp_strip_all_tags( $order->get_payment_method_title() ),
			'customer_id'          => $order->get_customer_id(),
			'billing_country'      => $order->get_billing_country(),
			'shipping_country'     => $order->get_shipping_country(),
		];
	}
	
	public function annotations(): array {
		return [
			'title'           => 'Search WC Orders',
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false,
		];
	}

	public function output_schema(): ?array {
		return [
			'type'       => 'object',
			'required'   => [ 'orders', 'limit', 'page' ],
			'properties' => [
				'orders' => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'required'   => [
							'id',
							'number',
							'status',
							'date_created',
							'currency',
							'total',
							'item_count',
							'payment_method',
							'payment_method_title',
							'customer_id',
							'billing_country',
							'shipping_country',
						],
						'properties' => [
							'id'                   => [ 'type' => 'integer' ],
							'number'               => [ 'type' => 'string' ],
							'status'               => [ 'type' => 'string' ],
							'date_created'         => [ 'type' => [ 'string', 'null' ], 'format' => 'date-time' ],
							'currency'             => [ 'type' => 'string' ],
							'total'                => [ 'type' => 'string' ],
							'item_count'           => [ 'type' => 'integer' ],
							'payment_method'       => [ 'type' => 'string' ],
							'payment_method_title' => [ 'type' => 'string' ],
							'customer_id'          => [ 'type' => 'integer' ],
							'billing_country'      => [ 'type' => 'string' ],
							'shipping_country'     => [ 'type' => 'string' ],
						],
					],
				],

				'limit'  => [ 'type' => 'integer' ],
				'page'   => [ 'type' => 'integer' ],
			],
		];
	}
}