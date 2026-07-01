<?php

namespace WP_MCP_Server\Tools\WordPress;

use WP_MCP_Server\Services\MediaFormatter;
use WP_MCP_Server\Tools\Contracts\ToolInterface;
use WP_MCP_Server\Tools\ToolResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GetMediaTool implements ToolInterface {

	public function name(): string {
		return 'wp_get_media';
	}

	public function description(): string {
		return 'Gets details for a WordPress media attachment by ID.';
	}

	public function input_schema(): array {
		return [
			'type'       => 'object',
			'required'   => [ 'id' ],
			'properties' => [
				'id' => [
					'type'        => 'integer',
					'description' => 'The WordPress attachment ID.',
				],
			],
		];
	}
	
	public function required_scopes(): array {
		return [ 'media:read' ];
	}

	public function execute( array $arguments = [] ): array {
		$attachment_id = isset( $arguments['id'] ) ? absint( $arguments['id'] ) : 0;

		if ( ! $attachment_id ) {
			return ToolResponse::error( 'Missing or invalid media ID.' );
		}

		$attachment = get_post( $attachment_id );

		if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
			return ToolResponse::error( 'Media attachment not found.' );
		}

		$formatter = new MediaFormatter();

		return ToolResponse::json(
			[
				'media' => $formatter->summary( $attachment ),
			]
		);
	}
	
	public function annotations(): array {
		return [
			'title'           => 'Get WP media attachment by ID.',
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false,
		];
	}

	public function output_schema(): ?array {
		return [
			'type'       => 'object',
			'required'   => [ 'media' ],
			'properties' => [
				'media' => MediaFormatter::summary_schema(),
			],
		];
	}
}