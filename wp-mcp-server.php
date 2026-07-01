<?php
/**
 * Plugin Name: WP MCP Server
 * Description: Model Context Protocol server for WordPress.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.2
 * Author: Creatricx
 * Text Domain: wp-mcp-server
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP_MCP_SERVER_VERSION', '0.1.0' );
define( 'WP_MCP_SERVER_FILE', __FILE__ );
define( 'WP_MCP_SERVER_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_MCP_SERVER_URL', plugin_dir_url( __FILE__ ) );

require_once WP_MCP_SERVER_PATH . 'vendor/autoload.php';

require_once WP_MCP_SERVER_PATH . 'includes/Admin/OAuthClientsPage.php';
require_once WP_MCP_SERVER_PATH . 'includes/Admin/OAuthStatusPage.php';
require_once WP_MCP_SERVER_PATH . 'includes/Admin/OAuthConnectedAppsPage.php';

require_once WP_MCP_SERVER_PATH . 'includes/Auth/OAuth/ScopeRegistry.php';
require_once WP_MCP_SERVER_PATH . 'includes/Auth/OAuth/OAuthInstaller.php';
require_once WP_MCP_SERVER_PATH . 'includes/Auth/OAuth/CryptKeyManager.php';
require_once WP_MCP_SERVER_PATH . 'includes/Auth/OAuth/OAuthServerFactory.php';
require_once WP_MCP_SERVER_PATH . 'includes/Auth/OAuth/OAuthRequestAuth.php';

require_once WP_MCP_SERVER_PATH . 'includes/HTTP/Controllers/OAuthMetadataController.php';
require_once WP_MCP_SERVER_PATH . 'includes/HTTP/Controllers/OAuthAuthorizeController.php';
require_once WP_MCP_SERVER_PATH . 'includes/HTTP/Controllers/OAuthTokenController.php';

require_once WP_MCP_SERVER_PATH . 'includes/Auth/OAuth/Entities/ClientEntity.php';
require_once WP_MCP_SERVER_PATH . 'includes/Auth/OAuth/Entities/ScopeEntity.php';
require_once WP_MCP_SERVER_PATH . 'includes/Auth/OAuth/Entities/UserEntity.php';
require_once WP_MCP_SERVER_PATH . 'includes/Auth/OAuth/Entities/AuthCodeEntity.php';
require_once WP_MCP_SERVER_PATH . 'includes/Auth/OAuth/Entities/AccessTokenEntity.php';
require_once WP_MCP_SERVER_PATH . 'includes/Auth/OAuth/Entities/RefreshTokenEntity.php';

require_once WP_MCP_SERVER_PATH . 'includes/Auth/OAuth/Repositories/GrantRepository.php';
require_once WP_MCP_SERVER_PATH . 'includes/Auth/OAuth/Repositories/ClientRepository.php';
require_once WP_MCP_SERVER_PATH . 'includes/Auth/OAuth/Repositories/ScopeRepository.php';
require_once WP_MCP_SERVER_PATH . 'includes/Auth/OAuth/Repositories/AuthCodeRepository.php';
require_once WP_MCP_SERVER_PATH . 'includes/Auth/OAuth/Repositories/AccessTokenRepository.php';
require_once WP_MCP_SERVER_PATH . 'includes/Auth/OAuth/Repositories/RefreshTokenRepository.php';

require_once WP_MCP_SERVER_PATH . 'includes/Core/Plugin.php';
require_once WP_MCP_SERVER_PATH . 'includes/Core/Settings.php';

require_once WP_MCP_SERVER_PATH . 'includes/HTTP/RestRouter.php';
require_once WP_MCP_SERVER_PATH . 'includes/HTTP/Controllers/MCPController.php';

require_once WP_MCP_SERVER_PATH . 'includes/MCP/Server.php';
require_once WP_MCP_SERVER_PATH . 'includes/MCP/Dispatcher.php';
require_once WP_MCP_SERVER_PATH . 'includes/MCP/Request.php';
require_once WP_MCP_SERVER_PATH . 'includes/MCP/Response.php';

require_once WP_MCP_SERVER_PATH . 'includes/Tools/BuiltInToolProvider.php';
require_once WP_MCP_SERVER_PATH . 'includes/Tools/Contracts/ToolInterface.php';
require_once WP_MCP_SERVER_PATH . 'includes/Tools/ToolResponse.php';
require_once WP_MCP_SERVER_PATH . 'includes/Tools/ToolRegistry.php';

require_once WP_MCP_SERVER_PATH . 'includes/Tools/System/PingTool.php';
require_once WP_MCP_SERVER_PATH . 'includes/Tools/System/SystemStatusTool.php';
require_once WP_MCP_SERVER_PATH . 'includes/Tools/System/SiteSettingsTool.php';
require_once WP_MCP_SERVER_PATH . 'includes/Tools/System/ActivePluginsTool.php';
require_once WP_MCP_SERVER_PATH . 'includes/Tools/System/ThemeInfoTool.php';
require_once WP_MCP_SERVER_PATH . 'includes/Tools/System/RewriteStatusTool.php';

require_once WP_MCP_SERVER_PATH . 'includes/Tools/WordPress/SearchPostsTool.php';
require_once WP_MCP_SERVER_PATH . 'includes/Tools/WordPress/GetPostTool.php';
require_once WP_MCP_SERVER_PATH . 'includes/Tools/WordPress/SearchMediaTool.php';
require_once WP_MCP_SERVER_PATH . 'includes/Tools/WordPress/GetMediaTool.php';

require_once WP_MCP_SERVER_PATH . 'includes/Tools/WooCommerce/SearchProductsTool.php';
require_once WP_MCP_SERVER_PATH . 'includes/Tools/WooCommerce/GetProductTool.php';
require_once WP_MCP_SERVER_PATH . 'includes/Tools/WooCommerce/ListProductCategoriesTool.php';
require_once WP_MCP_SERVER_PATH . 'includes/Tools/WooCommerce/GetProductVariationsTool.php';
require_once WP_MCP_SERVER_PATH . 'includes/Tools/WooCommerce/ListProductAttributesTool.php';
require_once WP_MCP_SERVER_PATH . 'includes/Tools/WooCommerce/GetProductReviewsTool.php';

require_once WP_MCP_SERVER_PATH . 'includes/Tools/WooCommerce/SearchOrdersTool.php';
require_once WP_MCP_SERVER_PATH . 'includes/Tools/WooCommerce/GetOrderTool.php';

require_once WP_MCP_SERVER_PATH . 'includes/Tools/WooCommerce/System/GetSettingsTool.php';
require_once WP_MCP_SERVER_PATH . 'includes/Tools/WooCommerce/System/GetStoreStatusTool.php';
require_once WP_MCP_SERVER_PATH . 'includes/Tools/WooCommerce/System/GetShippingZonesTool.php';
require_once WP_MCP_SERVER_PATH . 'includes/Tools/WooCommerce/System/GetPaymentGatewaysTool.php';
require_once WP_MCP_SERVER_PATH . 'includes/Tools/WooCommerce/System/GetTaxClassesTool.php';

require_once WP_MCP_SERVER_PATH . 'includes/Services/PostFormatter.php';
require_once WP_MCP_SERVER_PATH . 'includes/Services/MediaFormatter.php';
require_once WP_MCP_SERVER_PATH . 'includes/Services/WooProductFormatter.php';
require_once WP_MCP_SERVER_PATH . 'includes/Services/WooProductResolver.php';

require_once WP_MCP_SERVER_PATH . 'includes/Utilities/Logger.php';

add_action(
	'plugins_loaded',
	function () {
		$plugin = new WP_MCP_Server\Core\Plugin();
		$plugin->boot();
	}
);

register_activation_hook(
	__FILE__,
	function () {
		\WP_MCP_Server\Auth\OAuth\OAuthInstaller::install();

		$plugin = new \WP_MCP_Server\Core\Plugin();
		$plugin->activate();
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		$plugin = new WP_MCP_Server\Core\Plugin();
		$plugin->deactivate();
	}
);

