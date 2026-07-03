<?php

namespace WP_MCP_Server\Core;

use WP_MCP_Server\HTTP\RestRouter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin {

	public function boot(): void {
		$this->register_hooks();
	}

	private function register_hooks(): void {
		// RestAPI
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

		// Custom Endpoint
		add_action( 'init', [ $this, 'register_rewrite_rules' ] );
		add_filter( 'query_vars', [ $this, 'register_query_vars' ] );
		add_action( 'template_redirect', [ $this, 'handle_mcp_endpoint' ] );
		
		// OAuth Admin Page
		if ( is_admin() ) {
			( new \WP_MCP_Server\Admin\OAuthClientsPage() )->register();
			( new \WP_MCP_Server\Admin\OAuthStatusPage() )->register();
			( new \WP_MCP_Server\Admin\OAuthConnectedAppsPage() )->register();
			( new \WP_MCP_Server\Admin\RestApiTokenPage() )->register();
		}
	}

	public function register_rest_routes(): void {
		$router = new RestRouter();
		$router->register();
	}

	public function activate(): void {
		$this->register_rewrite_rules();
		flush_rewrite_rules();
	}

	public function deactivate(): void {
		flush_rewrite_rules();
	}

	public function register_rewrite_rules(): void {
		add_rewrite_rule(
			'^mcp/?$',
			'index.php?wp_mcp_server=1',
			'top'
		);

		add_rewrite_rule(
			'^\.well-known/oauth-protected-resource/?$',
			'index.php?wp_mcp_oauth_metadata=protected_resource',
			'top'
		);

		add_rewrite_rule(
			'^\.well-known/oauth-authorization-server/?$',
			'index.php?wp_mcp_oauth_metadata=authorization_server',
			'top'
		);
		
		add_rewrite_rule( '^authorize/?$', 'index.php?wp_mcp_oauth_authorize=1', 'top' );
		add_rewrite_rule( '^token/?$', 'index.php?wp_mcp_oauth_token=1', 'top' );
	}

	public function register_query_vars( array $vars ): array {
		$vars[] = 'wp_mcp_server';
		$vars[] = 'wp_mcp_oauth_metadata';
		$vars[] = 'wp_mcp_oauth_authorize';
		$vars[] = 'wp_mcp_oauth_token';

		return $vars;
	}

	
	public function handle_mcp_endpoint(): void {
		$metadata = get_query_var( 'wp_mcp_oauth_metadata' );

		if ( $metadata ) {
			$controller = new \WP_MCP_Server\HTTP\Controllers\OAuthMetadataController();

			if ( 'protected_resource' === $metadata ) {
				$controller->protected_resource();
				exit;
			}

			if ( 'authorization_server' === $metadata ) {
				$controller->authorization_server();
				exit;
			}
		}

		if ( '1' === get_query_var( 'wp_mcp_oauth_authorize' ) ) {
			( new \WP_MCP_Server\HTTP\Controllers\OAuthAuthorizeController() )->handle();
			exit;
		}

		if ( '1' === get_query_var( 'wp_mcp_oauth_token' ) ) {
			( new \WP_MCP_Server\HTTP\Controllers\OAuthTokenController() )->handle();
			exit;
		}

		if ( '1' !== get_query_var( 'wp_mcp_server' ) ) {
			return;
		}

		$controller = new \WP_MCP_Server\HTTP\Controllers\MCPController();
		$controller->handle_raw_http_request();

		exit;
	}

}