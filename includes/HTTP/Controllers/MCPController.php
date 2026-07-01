<?php

namespace WP_MCP_Server\HTTP\Controllers;

use WP_MCP_Server\MCP\Server;
use WP_MCP_Server\Auth\OAuth\OAuthRequestAuth;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MCPController {

	public function server_info( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response(
			[
				'name'        => 'WordPress MCP Server',
				'version'     => WP_MCP_SERVER_VERSION,
				'description' => 'A WordPress plugin that exposes MCP-compatible tools.',
				'status'      => 'ok',
			],
			200
		);
	}

	public function handle( WP_REST_Request $request ): WP_REST_Response {
		$body = $request->get_json_params();

		if ( ! is_array( $body ) ) {
			$body = [];
		}

		$server   = new Server();
		$response = $server->handle( $body );

		return new WP_REST_Response( $response, 200 );
	}
	
	
	public function handle_raw_http_request(): void {
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			status_header( 405 );
			wp_send_json(
				[
					'error' => 'Method not allowed. Use POST.',
				]
			);
		}

		$auth = OAuthRequestAuth::verify();

		if ( empty( $auth['valid'] ) ) {
			OAuthRequestAuth::unauthorized();
		}

		$raw_body = file_get_contents( 'php://input' );
		$body     = json_decode( $raw_body, true );

		if ( ! is_array( $body ) ) {
			$body = [];
		}

		$server   = new Server( $auth );
		$response = $server->handle( $body );

		status_header( 200 );
		wp_send_json( $response );
	}
	
	
}