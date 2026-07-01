<?php

namespace WP_MCP_Server\Tools\WooCommerce\System;

use WP_MCP_Server\Tools\Contracts\ToolInterface;
use WP_MCP_Server\Tools\ToolResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GetSettingsTool implements ToolInterface {

	public function name(): string {
		return 'wc_get_settings';
	}

	public function description(): string {
		return 'Gets safe read-only WooCommerce store settings without exposing secrets or private credentials.';
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
			'store'            => $this->get_store_settings(),
			'pages'            => $this->get_page_settings(),
			'tax'              => $this->get_tax_settings(),
			'shipping'         => $this->get_shipping_settings(),
			'payment_gateways' => $this->get_payment_gateways(),
		];

		return ToolResponse::json( $data );
	}

	private function get_store_settings(): array {
		return [
			'currency'                 => get_woocommerce_currency(),
			'currency_symbol'          => get_woocommerce_currency_symbol(),
			'price_decimals'           => wc_get_price_decimals(),
			'price_thousand_separator' => wc_get_price_thousand_separator(),
			'price_decimal_separator'  => wc_get_price_decimal_separator(),
			'weight_unit'              => get_option( 'woocommerce_weight_unit' ),
			'dimension_unit'           => get_option( 'woocommerce_dimension_unit' ),
			'default_country'          => get_option( 'woocommerce_default_country' ),
			'selling_locations'        => get_option( 'woocommerce_allowed_countries' ),
			'shipping_locations'       => get_option( 'woocommerce_ship_to_countries' ),
		];
	}

	private function get_page_settings(): array {
		return [
			'shop_page_id'      => absint( get_option( 'woocommerce_shop_page_id' ) ),
			'cart_page_id'      => absint( get_option( 'woocommerce_cart_page_id' ) ),
			'checkout_page_id'  => absint( get_option( 'woocommerce_checkout_page_id' ) ),
			'myaccount_page_id' => absint( get_option( 'woocommerce_myaccount_page_id' ) ),
			'terms_page_id'     => absint( get_option( 'woocommerce_terms_page_id' ) ),
		];
	}

	private function get_tax_settings(): array {
		return [
			'enabled'                    => 'yes' === get_option( 'woocommerce_calc_taxes' ),
			'prices_include_tax'         => 'yes' === get_option( 'woocommerce_prices_include_tax' ),
			'tax_based_on'               => get_option( 'woocommerce_tax_based_on' ),
			'shipping_tax_class'         => get_option( 'woocommerce_shipping_tax_class' ),
			'display_shop_including_tax' => get_option( 'woocommerce_tax_display_shop' ),
			'display_cart_including_tax' => get_option( 'woocommerce_tax_display_cart' ),
			'price_display_suffix'       => get_option( 'woocommerce_price_display_suffix' ),
		];
	}

	private function get_shipping_settings(): array {
		$zones_count = 0;

		if ( class_exists( '\WC_Shipping_Zones' ) ) {
			$zones       = \WC_Shipping_Zones::get_zones();
			$zones_count = is_array( $zones ) ? count( $zones ) : 0;
		}

		return [
			'enabled'              => 'yes' === get_option( 'woocommerce_ship_to_countries' ) || '' !== get_option( 'woocommerce_ship_to_countries' ),
			'calc_enabled'         => 'yes' === get_option( 'woocommerce_enable_shipping_calc' ),
			'hide_shipping_costs'  => 'yes' === get_option( 'woocommerce_shipping_cost_requires_address' ),
			'shipping_zones_count' => $zones_count,
		];
	}

	private function get_payment_gateways(): array {
		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
			return [];
		}

		$gateways = WC()->payment_gateways()->payment_gateways();
		$data     = [];

		foreach ( $gateways as $gateway ) {
			$data[] = [
				'id'          => $gateway->id,
				'title'       => wp_strip_all_tags( $gateway->get_title() ),
				'description' => wp_strip_all_tags( $gateway->get_description() ),
				'enabled'     => 'yes' === $gateway->enabled,
				//'supports'    => method_exists( $gateway, 'supports' ) ? $gateway->supports : [],
				'supports' => property_exists( $gateway, 'supports' ) && is_array( $gateway->supports )
				? array_values( $gateway->supports )
				: [],
			];
		}

		return $data;
	}
	
	public function annotations(): array {
		return [
			'title'           => 'Get WooCommerce Settings',
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false,
		];
	}

	public function output_schema(): ?array {
		return [
			'type'       => 'object',
			'required'   => [ 'store', 'pages', 'tax', 'shipping', 'payment_gateways' ],
			'properties' => [
				'store'            => [
					'type'       => 'object',
					'required'   => [
						'currency',
						'currency_symbol',
						'price_decimals',
						'price_thousand_separator',
						'price_decimal_separator',
						'weight_unit',
						'dimension_unit',
						'default_country',
						'selling_locations',
						'shipping_locations',
					],
					'properties' => [
						'currency'                 => [ 'type' => 'string' ],
						'currency_symbol'          => [ 'type' => 'string' ],
						'price_decimals'           => [ 'type' => 'integer' ],
						'price_thousand_separator' => [ 'type' => 'string' ],
						'price_decimal_separator'  => [ 'type' => 'string' ],
						'weight_unit'              => [ 'type' => 'string' ],
						'dimension_unit'           => [ 'type' => 'string' ],
						'default_country'          => [ 'type' => 'string' ],
						'selling_locations'        => [ 'type' => 'string' ],
						'shipping_locations'       => [ 'type' => 'string' ],
					],
				],

				'pages'            => [
					'type'       => 'object',
					'required'   => [
						'shop_page_id',
						'cart_page_id',
						'checkout_page_id',
						'myaccount_page_id',
						'terms_page_id',
					],
					'properties' => [
						'shop_page_id'      => [ 'type' => 'integer' ],
						'cart_page_id'      => [ 'type' => 'integer' ],
						'checkout_page_id'  => [ 'type' => 'integer' ],
						'myaccount_page_id' => [ 'type' => 'integer' ],
						'terms_page_id'     => [ 'type' => 'integer' ],
					],
				],

				'tax'              => [
					'type'       => 'object',
					'required'   => [
						'enabled',
						'prices_include_tax',
						'tax_based_on',
						'shipping_tax_class',
						'display_shop_including_tax',
						'display_cart_including_tax',
						'price_display_suffix',
					],
					'properties' => [
						'enabled'                    => [ 'type' => 'boolean' ],
						'prices_include_tax'         => [ 'type' => 'boolean' ],
						'tax_based_on'               => [ 'type' => 'string' ],
						'shipping_tax_class'         => [ 'type' => 'string' ],
						'display_shop_including_tax' => [ 'type' => 'string' ],
						'display_cart_including_tax' => [ 'type' => 'string' ],
						'price_display_suffix'       => [ 'type' => 'string' ],
					],
				],

				'shipping'         => [
					'type'       => 'object',
					'required'   => [
						'enabled',
						'calc_enabled',
						'hide_shipping_costs',
						'shipping_zones_count',
					],
					'properties' => [
						'enabled'              => [ 'type' => 'boolean' ],
						'calc_enabled'         => [ 'type' => 'boolean' ],
						'hide_shipping_costs'  => [ 'type' => 'boolean' ],
						'shipping_zones_count' => [ 'type' => 'integer' ],
					],
				],

				'payment_gateways' => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'required'   => [ 'id', 'title', 'description', 'enabled', 'supports' ],
						'properties' => [
							'id'          => [ 'type' => 'string' ],
							'title'       => [ 'type' => 'string' ],
							'description' => [ 'type' => 'string' ],
							'enabled'     => [ 'type' => 'boolean' ],
							'supports'    => [
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