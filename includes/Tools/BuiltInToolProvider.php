<?php
namespace WP_MCP_Server\Tools;

use WP_MCP_Server\Tools\System\PingTool;
use WP_MCP_Server\Tools\System\SystemStatusTool;
use WP_MCP_Server\Tools\System\SiteSettingsTool;
use WP_MCP_Server\Tools\System\ActivePluginsTool;
use WP_MCP_Server\Tools\System\ThemeInfoTool;
use WP_MCP_Server\Tools\System\RewriteStatusTool;

use WP_MCP_Server\Tools\WordPress\SearchPostsTool;
use WP_MCP_Server\Tools\WordPress\GetPostTool;
use WP_MCP_Server\Tools\WordPress\SearchMediaTool;
use WP_MCP_Server\Tools\WordPress\GetMediaTool;

use WP_MCP_Server\Tools\WooCommerce\System\GetSettingsTool as WooSettingsTool;
use WP_MCP_Server\Tools\WooCommerce\System\GetStoreStatusTool as WooStoreStatusTool;
use WP_MCP_Server\Tools\WooCommerce\System\GetShippingZonesTool as WooShippingZonesTool;
use WP_MCP_Server\Tools\WooCommerce\System\GetPaymentGatewaysTool as WooPaymentGatewaysTool;
use WP_MCP_Server\Tools\WooCommerce\System\GetTaxClassesTool as WooTaxClassesTool;

use WP_MCP_Server\Tools\WooCommerce\SearchProductsTool as WooSearchProductsTool;
use WP_MCP_Server\Tools\WooCommerce\SearchProductsTool as WooGetProductTool;
use WP_MCP_Server\Tools\WooCommerce\ListProductCategoriesTool as WooGetCategoriesTool;
use WP_MCP_Server\Tools\WooCommerce\GetProductVariationsTool as WooGetVariationsTool;
use WP_MCP_Server\Tools\WooCommerce\ListProductAttributesTool as WooGetAttributesTool;
use WP_MCP_Server\Tools\WooCommerce\GetProductReviewsTool as WooGetReviewsTool;

use WP_MCP_Server\Tools\WooCommerce\SearchOrdersTool as WooSearchOrdersTool;
use WP_MCP_Server\Tools\WooCommerce\GetOrderTool as WooGetOrderTool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BuiltInToolProvider {

	public function register( ToolRegistry $registry ): void {
		$registry->register( new PingTool() );
		$registry->register( new SystemStatusTool() );
		$registry->register( new SiteSettingsTool() );
		$registry->register( new ActivePluginsTool() );
		$registry->register( new ThemeInfoTool() );
		$registry->register( new RewriteStatusTool() );
		
		$registry->register( new SearchPostsTool() );
		$registry->register( new GetPostTool() );
		$registry->register( new SearchMediaTool() );
		$registry->register( new GetMediaTool() );
		
		$registry->register( new WooSettingsTool() );
		$registry->register( new WooStoreStatusTool() );
		$registry->register( new WooShippingZonesTool() );
		$registry->register( new WooPaymentGatewaysTool() );
		$registry->register( new WooTaxClassesTool() );
		
		$registry->register( new WooSearchProductsTool() );
		$registry->register( new WooGetProductTool() );
		$registry->register( new WooGetCategoriesTool() );
		$registry->register( new WooGetVariationsTool() );
		$registry->register( new WooGetReviewsTool() );
		
		$registry->register( new WooSearchOrdersTool() );
		$registry->register( new WooGetOrderTool() );
	}
}