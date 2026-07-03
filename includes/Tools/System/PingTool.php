<?php

namespace WP_MCP_Server\Tools\System;

use WP_MCP_Server\Tools\Contracts\ToolInterface;
use WP_MCP_Server\Tools\ToolResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PingTool implements ToolInterface {

	public function name(): string {
		return 'wp_ping';
	}

	public function description(): string {
		return 'Checks if the WordPress MCP server is responding.';
	}

	public function input_schema(): array {
		return [
			'type'       => 'object',
			'properties' => new \stdClass(),
		];
	}
	
	public function required_scopes(): array {
		return [ 'wp:read' ];
	}

	public function execute( array $arguments = [] ): array {
		return ToolResponse::text( 'WordPress MCP server is working.' );
	}
	
	public function annotations(): array {
		return [
			'title'           => 'Check MCP Ping',
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false,
		];
	}

	public function output_schema(): ?array {
		return [
			'type'       => 'object',
			'required'   => [ 'content' ],
			'properties' => [
				'content' => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'required'   => [ 'type', 'text' ],
						'properties' => [
							'type' => [
								'type' => 'string',
								'enum' => [ 'text' ],
							],
							'text' => [
								'type' => 'string',
							],
						],
					],
				],
			],
		];
	}
}