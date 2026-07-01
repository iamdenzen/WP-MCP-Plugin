<?php

namespace WP_MCP_Server\Tools\WooCommerce\System;

use WP_MCP_Server\Tools\Contracts\ToolInterface;
use WP_MCP_Server\Tools\ToolResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GetPaymentGatewaysTool implements ToolInterface {

	public function name(): string {
		return 'wc_get_payment_gateways';
	}

	public function description(): string {
		return 'Gets safe read-only WooCommerce payment gateways without exposing private credentials.';
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
		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
			return ToolResponse::error( 'WooCommerce payment gateways are not available.' );
		}

		$data = [];

		foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
			$data[] = [
				'id'                 => $gateway->id,
				'title'              => wp_strip_all_tags( $gateway->get_title() ),
				'description'        => wp_strip_all_tags( $gateway->get_description() ),
				'enabled'            => 'yes' === $gateway->enabled,
				'method_title'       => wp_strip_all_tags( $gateway->get_method_title() ),
				'method_description' => wp_strip_all_tags( $gateway->get_method_description() ),
				//'supports'           => is_array( $gateway->supports ) ? array_values( $gateway->supports ) : [],
				'supports' => property_exists( $gateway, 'supports' ) && is_array( $gateway->supports )
				? array_values( $gateway->supports )
				: [],
			];
		}

		return ToolResponse::json(
			[
				'payment_gateways' => $data,
			]
		);
	}
	
	public function annotations(): array {
		return [
			'title'           => 'Get WooCommerce Payment Gateways',
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false,
		];
	}

	public function output_schema(): ?array {
		return [
			'type'       => 'object',
			'required'   => [ 'payment_gateways' ],
			'properties' => [
				'payment_gateways' => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'required'   => [
							'id',
							'title',
							'description',
							'enabled',
							'method_title',
							'method_description',
							'supports',
						],
						'properties' => [
							'id'                 => [ 'type' => 'string' ],
							'title'              => [ 'type' => 'string' ],
							'description'        => [ 'type' => 'string' ],
							'enabled'            => [ 'type' => 'boolean' ],
							'method_title'       => [ 'type' => 'string' ],
							'method_description' => [ 'type' => 'string' ],
							'supports'           => [
								'type'  => 'array',
								'items' => [ 'type' => 'string' ],
							],
						],
					],
				],
			],
		];
	}
}