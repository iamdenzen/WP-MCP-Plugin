<?php

namespace WP_MCP_Server\MCP;

use WP_MCP_Server\Tools\ToolRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Dispatcher {

	private ToolRegistry $tools;
	private array $auth_context;

	public function __construct( ToolRegistry $tools, array $auth_context = [] ) {
		$this->tools        = $tools;
		$this->auth_context = $auth_context;
	}

	public function dispatch( Request $request ): array {
		return match ( $request->get_method() ) {
			'initialize' => $this->initialize( $request ),
			'ping'       => $this->ping( $request ),
			'tools/list' => $this->tools_list( $request ),
			'tools/call' => $this->tools_call( $request ),
			default      => Response::error(
				$request->get_id(),
				-32601,
				'Method not found'
			),
		};
	}

	private function initialize( Request $request ): array {
		return Response::result(
			$request->get_id(),
			[
				'protocolVersion' => '2025-03-26',
				'serverInfo'      => [
					'name'    => 'wp-mcp-server',
					'version' => WP_MCP_SERVER_VERSION,
				],
				'capabilities'    => [
					'tools' => new \stdClass(),
				],
			]
		);
	}

	private function ping( Request $request ): array {
		return Response::result( $request->get_id(), [] );
	}

	private function tools_list( Request $request ): array {
		return Response::result(
			$request->get_id(),
			[
				'tools' => $this->tools->to_mcp_list(),
			]
		);
	}

	private function tools_call( Request $request ): array {
		$params = $request->get_params();

		$name      = isset( $params['name'] ) ? sanitize_key( $params['name'] ) : '';
		$arguments = isset( $params['arguments'] ) && is_array( $params['arguments'] )
			? $params['arguments']
			: [];

		if ( '' === $name || ! $this->tools->has( $name ) ) {
			return Response::error(
				$request->get_id(),
				-32602,
				'Invalid tool name'
			);
		}

		$tool = $this->tools->get( $name );
		
		$required_scopes = method_exists( $tool, 'required_scopes' )
			? $tool->required_scopes()
			: [];

		$token_scopes = $this->auth_context['scopes'] ?? [];

		foreach ( $required_scopes as $required_scope ) {
			if ( ! in_array( $required_scope, $token_scopes, true ) ) {
				return Response::error(
					$request->get_id(),
					-32003,
					'Forbidden. Missing required scope: ' . $required_scope
				);
			}
		}

		return Response::result(
			$request->get_id(),
			$tool->execute( $arguments )
		);
	}
}