<?php

namespace WP_MCP_Server\Tools\WooCommerce;

use WP_MCP_Server\Tools\Contracts\ToolInterface;
use WP_MCP_Server\Tools\ToolResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GetOrderTool implements ToolInterface {

	public function name(): string {
		return 'wc_get_order';
	}

	public function description(): string {
		return 'Gets safe read-only details for a WooCommerce order by ID.';
	}

	public function input_schema(): array {
		return [
			'type'       => 'object',
			'required'   => [ 'id' ],
			'properties' => [
				'id' => [
					'type'        => 'integer',
					'description' => 'WooCommerce order ID.',
				],
			],
		];
	}

	public function required_scopes(): array {
		return [ 'woocommerce:read' ];
	}

	public function execute( array $arguments = [] ): array {
		$order_id = isset( $arguments['id'] ) ? absint( $arguments['id'] ) : 0;

		if ( ! $order_id || ! function_exists( 'wc_get_order' ) ) {
			return ToolResponse::error( 'Missing or invalid order ID.' );
		}

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return ToolResponse::error( 'Order not found.' );
		}

		return ToolResponse::json( $this->format_order( $order ) );
	}

	private function format_order( \WC_Order $order ): array {
		return [
			'id'                   => $order->get_id(),
			'number'               => $order->get_order_number(),
			'status'               => $order->get_status(),
			'date_created'         => $order->get_date_created() ? $order->get_date_created()->date( DATE_ATOM ) : null,
			'date_modified'        => $order->get_date_modified() ? $order->get_date_modified()->date( DATE_ATOM ) : null,
			'currency'             => $order->get_currency(),
			'total'                => $order->get_total(),
			'subtotal'             => $order->get_subtotal(),
			'discount_total'       => $order->get_discount_total(),
			'shipping_total'       => $order->get_shipping_total(),
			'tax_total'            => $order->get_total_tax(),
			'payment_method'       => $order->get_payment_method(),
			'payment_method_title' => wp_strip_all_tags( $order->get_payment_method_title() ),
			'customer'             => [
				'id'               => $order->get_customer_id(),
				'billing_country'  => $order->get_billing_country(),
				'shipping_country' => $order->get_shipping_country(),
			],
			'line_items'           => $this->get_line_items( $order ),
			'shipping_lines'       => $this->get_shipping_lines( $order ),
			'refunds'              => $this->get_refunds( $order ),
		];
	}

	private function get_line_items( \WC_Order $order ): array {
		$items = [];

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();

			$items[] = [
				'id'           => $item->get_id(),
				'name'         => wp_strip_all_tags( $item->get_name() ),
				'product_id'   => $item->get_product_id(),
				'variation_id' => $item->get_variation_id(),
				'sku'          => $product ? $product->get_sku() : null,
				'quantity'     => $item->get_quantity(),
				'subtotal'     => $item->get_subtotal(),
				'total'        => $item->get_total(),
				'tax_total'    => $item->get_total_tax(),
			];
		}

		return $items;
	}

	private function get_shipping_lines( \WC_Order $order ): array {
		$items = [];

		foreach ( $order->get_shipping_methods() as $shipping ) {
			$items[] = [
				'id'           => $shipping->get_id(),
				'method_id'    => $shipping->get_method_id(),
				'instance_id'  => $shipping->get_instance_id(),
				'method_title' => wp_strip_all_tags( $shipping->get_method_title() ),
				'total'        => $shipping->get_total(),
				'tax_total'    => $shipping->get_total_tax(),
			];
		}

		return $items;
	}

	private function get_refunds( \WC_Order $order ): array {
		$refunds = [];

		foreach ( $order->get_refunds() as $refund ) {
			$refunds[] = [
				'id'     => $refund->get_id(),
				'amount' => $refund->get_amount(),
				'reason' => wp_strip_all_tags( $refund->get_reason() ),
				'date'   => $refund->get_date_created() ? $refund->get_date_created()->date( DATE_ATOM ) : null,
			];
		}

		return $refunds;
	}
	
	public function annotations(): array {
		return [
			'title'           => 'Get WC Order',
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false,
		];
	}

	public function output_schema(): ?array {
		return [
			'type'       => 'object',
			'required' => [
				'id',
				'number',
				'status',
				'date_created',
				'date_modified',
				'currency',
				'total',
				'subtotal',
				'discount_total',
				'shipping_total',
				'tax_total',
				'payment_method',
				'payment_method_title',
				'customer',
				'line_items',
				'shipping_lines',
				'refunds',
			],
			'properties' => [
				'id'                   => [ 'type' => 'integer' ],
				'number'               => [ 'type' => 'string' ],
				'status'               => [ 'type' => 'string' ],
				'date_created'         => [ 'type' => [ 'string', 'null' ], 'format' => 'date-time' ],
				'date_modified'        => [ 'type' => [ 'string', 'null' ], 'format' => 'date-time' ],
				'currency'             => [ 'type' => 'string' ],
				'total'                => [ 'type' => 'string' ],
				'subtotal'             => [ 'type' => 'string' ],
				'discount_total'       => [ 'type' => 'string' ],
				'shipping_total'       => [ 'type' => 'string' ],
				'tax_total'            => [ 'type' => 'string' ],
				'payment_method'       => [ 'type' => 'string' ],
				'payment_method_title' => [ 'type' => 'string' ],

				'customer'             => [
					'type'       => 'object',
					'required'   => [ 'id', 'billing_country', 'shipping_country' ],
					'properties' => [
						'id'               => [ 'type' => 'integer' ],
						'billing_country'  => [ 'type' => 'string' ],
						'shipping_country' => [ 'type' => 'string' ],
					],
				],

				'line_items'           => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'required'   => [
							'id',
							'name',
							'product_id',
							'variation_id',
							'sku',
							'quantity',
							'subtotal',
							'total',
							'tax_total',
						],
						'properties' => [
							'id'           => [ 'type' => 'integer' ],
							'name'         => [ 'type' => 'string' ],
							'product_id'   => [ 'type' => 'integer' ],
							'variation_id' => [ 'type' => 'integer' ],
							'sku'          => [ 'type' => [ 'string', 'null' ] ],
							'quantity'     => [ 'type' => 'number' ],
							'subtotal'     => [ 'type' => 'string' ],
							'total'        => [ 'type' => 'string' ],
							'tax_total'    => [ 'type' => 'string' ],
						],
					],
				],

				'shipping_lines'       => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'required'   => [
							'id',
							'method_id',
							'instance_id',
							'method_title',
							'total',
							'tax_total',
						],
						'properties' => [
							'id'           => [ 'type' => 'integer' ],
							'method_id'    => [ 'type' => 'string' ],
							'instance_id'  => [ 'type' => 'string' ],
							'method_title' => [ 'type' => 'string' ],
							'total'        => [ 'type' => 'string' ],
							'tax_total'    => [ 'type' => 'string' ],
						],
					],
				],

				'refunds'              => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'required'   => [ 'id', 'amount', 'reason', 'date' ],
						'properties' => [
							'id'     => [ 'type' => 'integer' ],
							'amount' => [ 'type' => 'string' ],
							'reason' => [ 'type' => 'string' ],
							'date'   => [ 'type' => [ 'string', 'null' ], 'format' => 'date-time' ],
						],
					],
				],
			],
		];
	}
}