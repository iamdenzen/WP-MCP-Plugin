<?php

namespace WP_MCP_Server\Tools\WordPress;

use WP_MCP_Server\Tools\Contracts\ToolInterface;
use WP_MCP_Server\Tools\ToolResponse;
use WP_MCP_Server\Core\Settings;
use WP_MCP_Server\Services\PostFormatter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GetPostTool implements ToolInterface {

	public function name(): string {
		return 'wp_get_post';
	}

	public function description(): string {
		return 'Gets full details for a published WordPress post or page by ID.';
	}

	public function input_schema(): array {
		return [
			'type'       => 'object',
			'required'   => [ 'id' ],
			'properties' => [
				'id' => [
					'type'        => 'integer',
					'description' => 'The WordPress post ID.',
				],
			],
		];
	}
	
	public function required_scopes(): array {
		return [ 'posts:read' ];
	}

	public function execute( array $arguments = [] ): array {
		$post_id = isset( $arguments['id'] ) ? absint( $arguments['id'] ) : 0;

		if ( ! $post_id ) {
			return ToolResponse::error( 'Missing or invalid post ID.' );
		}

		$post = get_post( $post_id );

		if ( ! $post || 'publish' !== $post->post_status ) {
			return ToolResponse::error( 'Post not found.' );
		}

		if ( ! in_array( $post->post_type, Settings::allowed_post_types(), true ) ) {
			return ToolResponse::error( 'Unsupported post type.' );
		}

		$formatter = new PostFormatter();
		$data      = $formatter->full( $post );

		return ToolResponse::json( $data );
	}
	
	public function annotations(): array {
		return [
			'title'           => 'Get WP Post',
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false,
		];
	}

	public function output_schema(): ?array {
		return PostFormatter::full_schema();
	}

}