<?php

namespace WP_MCP_Server\HTTP;

use WP_MCP_Server\HTTP\Controllers\MCPController;
use WP_MCP_Server\HTTP\Controllers\RestToolsController;
use WP_MCP_Server\Auth\RestApiTokenAuth;

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
			'/tools',
			[
				'methods'             => 'GET',
				'callback'            => [ new RestToolsController(), 'list_tools' ],
				'permission_callback' => [ RestApiTokenAuth::class, 'verify' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/tools/call',
			[
				'methods'             => 'POST',
				'callback'            => [ new RestToolsController(), 'call_tool' ],
				'permission_callback' => [ RestApiTokenAuth::class, 'verify' ],
			]
		);
	}
}