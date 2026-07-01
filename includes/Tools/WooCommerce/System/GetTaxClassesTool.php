<?php

namespace WP_MCP_Server\Tools\WooCommerce\System;

use WP_MCP_Server\Tools\Contracts\ToolInterface;
use WP_MCP_Server\Tools\ToolResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GetTaxClassesTool implements ToolInterface {

	public function name(): string {
		return 'wc_get_tax_classes';
	}

	public function description(): string {
		return 'Gets safe read-only WooCommerce tax classes and basic tax configuration.';
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

		$classes = [];

		if ( class_exists( '\WC_Tax' ) ) {
			$classes = \WC_Tax::get_tax_classes();
		}

		array_unshift( $classes, 'Standard' );

		return ToolResponse::json(
			[
				'tax_enabled'        => 'yes' === get_option( 'woocommerce_calc_taxes' ),
				'prices_include_tax' => 'yes' === get_option( 'woocommerce_prices_include_tax' ),
				'tax_based_on'       => get_option( 'woocommerce_tax_based_on' ),
				'tax_classes'        => array_values( array_unique( $classes ) ),
			]
		);
	}
	
	public function annotations(): array {
		return [
			'title'           => 'Get WooCommerce Tax Classes',
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false,
		];
	}

	public function output_schema(): ?array {
		return [
			'type'       => 'object',
			'required'   => [
				'tax_enabled',
				'prices_include_tax',
				'tax_based_on',
				'tax_classes',
			],
			'properties' => [
				'tax_enabled'        => [ 'type' => 'boolean' ],
				'prices_include_tax' => [ 'type' => 'boolean' ],
				'tax_based_on'       => [ 'type' => 'string' ],
				'tax_classes'        => [
					'type'  => 'array',
					'items' => [ 'type' => 'string' ],
				],
			],
		];
	}
}