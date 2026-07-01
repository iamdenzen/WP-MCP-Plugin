<?php

namespace WP_MCP_Server\Tools\WooCommerce\System;

use WP_MCP_Server\Tools\Contracts\ToolInterface;
use WP_MCP_Server\Tools\ToolResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GetShippingZonesTool implements ToolInterface {

	public function name(): string {
		return 'wc_get_shipping_zones';
	}

	public function description(): string {
		return 'Gets safe read-only WooCommerce shipping zones and enabled shipping methods.';
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
		if ( ! class_exists( 'WooCommerce' ) || ! class_exists( '\WC_Shipping_Zones' ) ) {
			return ToolResponse::error( 'WooCommerce shipping is not available.' );
		}

		$zones = [];

		foreach ( \WC_Shipping_Zones::get_zones() as $zone ) {
			$zones[] = $this->format_zone( new \WC_Shipping_Zone( $zone['id'] ) );
		}

		$zones[] = $this->format_zone( new \WC_Shipping_Zone( 0 ) );

		return ToolResponse::json(
			[
				'zones' => $zones,
			]
		);
	}

	private function format_zone( \WC_Shipping_Zone $zone ): array {
		$methods = [];

		foreach ( $zone->get_shipping_methods() as $method ) {
			$methods[] = [
				'id'          => $method->id,
				'instance_id' => absint( $method->instance_id ),
				'title'       => wp_strip_all_tags( $method->get_title() ),
				'enabled'     => 'yes' === $method->enabled,
			];
		}

		return [
			'id'        => absint( $zone->get_id() ),
			'name'      => $zone->get_zone_name(),
			'locations' => $zone->get_zone_locations(),
			'methods'   => $methods,
		];
	}
	
	public function annotations(): array {
		return [
			'title'           => 'Get WooCommerce Shipping Zones',
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false,
		];
	}

	public function output_schema(): ?array {
		return [
			'type'       => 'object',
			'required'   => [ 'zones' ],
			'properties' => [
				'zones' => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'required'   => [ 'id', 'name', 'locations', 'methods' ],
						'properties' => [
							'id'        => [ 'type' => 'integer' ],
							'name'      => [ 'type' => 'string' ],

							'locations' => [
								'type'  => 'array',
								'items' => [
									'type'       => 'object',
									'required'   => [ 'code', 'type' ],
									'properties' => [
										'code' => [ 'type' => 'string' ],
										'type' => [ 'type' => 'string' ],
									],
								],
							],

							'methods'   => [
								'type'  => 'array',
								'items' => [
									'type'       => 'object',
									'required'   => [ 'id', 'instance_id', 'title', 'enabled' ],
									'properties' => [
										'id'          => [ 'type' => 'string' ],
										'instance_id' => [ 'type' => 'integer' ],
										'title'       => [ 'type' => 'string' ],
										'enabled'     => [ 'type' => 'boolean' ],
									],
								],
							],
						],
					],
				],
			],
		];
	}
}