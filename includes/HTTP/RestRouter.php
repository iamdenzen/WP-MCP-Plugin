<?php

namespace WP_MCP_Server\HTTP;

use WP_MCP_Server\HTTP\Controllers\MCPController;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RestRouter {

	private string $namespace = 'wp-mcp/v1';

	public function register(): void {
		register_rest_route(
			$this->namespace,
			'/server',
			[
				'methods'             => 'GET',
				'callback'            => [ new MCPController(), 'server_info' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			$this->namespace,
			'/mcp',
			[
				'methods'             => 'POST',
				'callback'            => [ new MCPController(), 'handle' ],
				'permission_callback' => '__return_true',
			]
		);
	}
}