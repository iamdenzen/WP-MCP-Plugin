<?php

namespace WP_MCP_Server\Tools\System;

use WP_MCP_Server\Tools\Contracts\ToolInterface;
use WP_MCP_Server\Tools\ToolResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RewriteStatusTool implements ToolInterface {

	public function name(): string {
		return 'wp_get_rewrite_status';
	}

	public function description(): string {
		return 'Gets safe read-only WordPress permalink and rewrite status information.';
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
		global $wp_rewrite;

		$permalink_structure = (string) get_option( 'permalink_structure' );
		$rewrite_rules       = get_option( 'rewrite_rules' );

		$data = [
			'permalinks' => [
				'structure'         => $permalink_structure,
				'using_pretty_urls' => '' !== $permalink_structure,
				'category_base'     => (string) get_option( 'category_base' ),
				'tag_base'          => (string) get_option( 'tag_base' ),
			],
			'rewrite' => [
				'rules_generated'        => is_array( $rewrite_rules ) && ! empty( $rewrite_rules ),
				'rules_count'            => is_array( $rewrite_rules ) ? count( $rewrite_rules ) : 0,
				'use_trailing_slashes'   => $wp_rewrite instanceof \WP_Rewrite ? $wp_rewrite->use_trailing_slashes : null,
				'index'                  => $wp_rewrite instanceof \WP_Rewrite ? $wp_rewrite->index : null,
				'pagination_base'        => $wp_rewrite instanceof \WP_Rewrite ? $wp_rewrite->pagination_base : null,
				'comments_pagination_base' => $wp_rewrite instanceof \WP_Rewrite ? $wp_rewrite->comments_pagination_base : null,
				'search_base'            => $wp_rewrite instanceof \WP_Rewrite ? $wp_rewrite->search_base : null,
				'author_base'            => $wp_rewrite instanceof \WP_Rewrite ? $wp_rewrite->author_base : null,
			],
			'endpoints' => [
				'mcp_clean_endpoint' => home_url( '/mcp' ),
				'rest_mcp_endpoint'  => rest_url( 'wp-mcp/v1/mcp' ),
			],
		];

		return ToolResponse::json( $data );
	}
	
	public function annotations(): array {
		return [
			'title'           => 'Get WP Rewrite Rules',
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false,
		];
	}

	public function output_schema(): ?array {
		return [
			'type'       => 'object',
			'required'   => [ 'permalinks', 'rewrite', 'endpoints' ],
			'properties' => [
				'permalinks' => [
					'type'       => 'object',
					'required'   => [
						'structure',
						'using_pretty_urls',
						'category_base',
						'tag_base',
					],
					'properties' => [
						'structure' => [ 'type' => 'string' ],
						'using_pretty_urls' => [ 'type' => 'boolean' ],
						'category_base' => [ 'type' => 'string' ],
						'tag_base' => [ 'type' => 'string' ],
					],
				],
				'rewrite' => [
					'type'       => 'object',
					'required'   => [
						'rules_generated',
						'rules_count',
						'use_trailing_slashes',
						'index',
						'pagination_base',
						'comments_pagination_base',
						'search_base',
						'author_base',
					],
					'properties' => [
						'rules_generated' => [ 'type' => 'boolean' ],
						'rules_count' => [ 'type' => 'integer' ],
						'use_trailing_slashes' => [
							'type' => [ 'boolean', 'null' ],
						],
						'index' => [
							'type' => [ 'string', 'null' ],
						],
						'pagination_base' => [
							'type' => [ 'string', 'null' ],
						],
						'comments_pagination_base' => [
							'type' => [ 'string', 'null' ],
						],
						'search_base' => [
							'type' => [ 'string', 'null' ],
						],
						'author_base' => [
							'type' => [ 'string', 'null' ],
						],
					],
				],
				'endpoints' => [
					'type'       => 'object',
					'required'   => [
						'mcp_clean_endpoint',
						'rest_mcp_endpoint',
					],
					'properties' => [
						'mcp_clean_endpoint' => [
							'type'   => 'string',
							'format' => 'uri',
						],
						'rest_mcp_endpoint' => [
							'type'   => 'string',
							'format' => 'uri',
						],
					],
				],
			],
		];
	}
}