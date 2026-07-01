<?php

namespace WP_MCP_Server\Tools\WooCommerce\System;

use WP_MCP_Server\Tools\Contracts\ToolInterface;
use WP_MCP_Server\Tools\ToolResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GetStoreStatusTool implements ToolInterface {

	public function name(): string {
		return 'wc_get_store_status';
	}

	public function description(): string {
		return 'Gets safe read-only WooCommerce store status and environment information.';
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

		$data = [
			'wordpress'   => [
				'version'   => get_bloginfo( 'version' ),
				'site_url'  => home_url(),
				'admin_url' => admin_url(),
			],
			'woocommerce' => [
				'version'        => defined( 'WC_VERSION' ) ? WC_VERSION : null,
				'currency'       => get_woocommerce_currency(),
				'currency_symbol'=> get_woocommerce_currency_symbol(),
				'hpos_enabled'   => class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' )
					? \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()
					: null,
			],
			'server'      => [
				'php_version' => PHP_VERSION,
			],
			'theme'       => [
				'name'    => wp_get_theme()->get( 'Name' ),
				'version' => wp_get_theme()->get( 'Version' ),
			],
			'features'    => [
				'taxes_enabled'    => 'yes' === get_option( 'woocommerce_calc_taxes' ),
				'coupons_enabled'  => 'yes' === get_option( 'woocommerce_enable_coupons' ),
				'reviews_enabled'  => 'yes' === get_option( 'woocommerce_enable_reviews' ),
			],
		];

		return ToolResponse::json( $data );
	}
	
	public function annotations(): array {
		return [
			'title'           => 'Get WooCommerce Store Status',
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false,
		];
	}

	public function output_schema(): ?array {
		return [
			'type'       => 'object',
			'required'   => [ 'wordpress', 'woocommerce', 'server', 'theme', 'features' ],
			'properties' => [
				'wordpress'   => [
					'type'       => 'object',
					'required'   => [ 'version', 'site_url', 'admin_url' ],
					'properties' => [
						'version'   => [ 'type' => 'string' ],
						'site_url'  => [ 'type' => 'string' ],
						'admin_url' => [ 'type' => 'string' ],
					],
				],

				'woocommerce' => [
					'type'       => 'object',
					'required'   => [ 'version', 'currency', 'currency_symbol', 'hpos_enabled' ],
					'properties' => [
						'version'         => [ 'type' => [ 'string', 'null' ] ],
						'currency'        => [ 'type' => 'string' ],
						'currency_symbol' => [ 'type' => 'string' ],
						'hpos_enabled'    => [ 'type' => [ 'boolean', 'null' ] ],
					],
				],

				'server'      => [
					'type'       => 'object',
					'required'   => [ 'php_version' ],
					'properties' => [
						'php_version' => [ 'type' => 'string' ],
					],
				],

				'theme'       => [
					'type'       => 'object',
					'required'   => [ 'name', 'version' ],
					'properties' => [
						'name'    => [ 'type' => 'string' ],
						'version' => [ 'type' => 'string' ],
					],
				],

				'features'    => [
					'type'       => 'object',
					'required'   => [ 'taxes_enabled', 'coupons_enabled', 'reviews_enabled' ],
					'properties' => [
						'taxes_enabled'   => [ 'type' => 'boolean' ],
						'coupons_enabled' => [ 'type' => 'boolean' ],
						'reviews_enabled' => [ 'type' => 'boolean' ],
					],
				],
			],
		];
	}
}