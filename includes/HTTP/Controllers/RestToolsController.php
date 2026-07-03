<?php

namespace WP_MCP_Server\HTTP\Controllers;

use WP_MCP_Server\MCP\Server;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RestToolsController {

	public function list_tools( WP_REST_Request $request ): WP_REST_Response {
		$server = new Server();

		$response = $server->handle(
			[
				'jsonrpc' => '2.0',
				'id'      => 1,
				'method'  => 'tools/list',
				'params'  => [],
			]
		);

		return new WP_REST_Response( $response['result'] ?? [], 200 );
	}

	public function call_tool( WP_REST_Request $request ): WP_REST_Response {
		$body = $request->get_json_params();

		if ( ! is_array( $body ) ) {
			$body = [];
		}

		$name      = isset( $body['name'] ) ? sanitize_key( $body['name'] ) : '';
		$arguments = isset( $body['arguments'] ) && is_array( $body['arguments'] )
			? $body['arguments']
			: [];

		if ( '' === $name ) {
			return new WP_REST_Response(
				[
					'error' => 'Missing tool name.',
				],
				400
			);
		}

		$server = new Server();

		$response = $server->handle(
			[
				'jsonrpc' => '2.0',
				'id'      => 1,
				'method'  => 'tools/call',
				'params'  => [
					'name'      => $name,
					'arguments' => $arguments,
				],
			]
		);

		if ( isset( $response['error'] ) ) {
			return new WP_REST_Response( $response['error'], 400 );
		}

		return new WP_REST_Response( $response['result'] ?? [], 200 );
	}
}