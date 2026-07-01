<?php

namespace WP_MCP_Server\Tools\WordPress;

use WP_MCP_Server\Services\MediaFormatter;
use WP_MCP_Server\Tools\Contracts\ToolInterface;
use WP_MCP_Server\Tools\ToolResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SearchMediaTool implements ToolInterface {

	public function name(): string {
		return 'wp_search_media';
	}

	public function description(): string {
		return 'Searches WordPress media attachments.';
	}
	
	public function required_scopes(): array {
		return [ 'media:read' ];
	}

	public function input_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'search' => [
					'type'        => 'string',
					'description' => 'Search keyword.',
				],
				'mime_type' => [
					'type'        => 'string',
					'description' => 'Optional MIME type filter, for example image/jpeg or application/pdf.',
				],
				'limit' => [
					'type'        => 'integer',
					'description' => 'Maximum results. Default 10, max 20.',
					'default'     => 10,
				],
			],
		];
	}

	public function execute( array $arguments = [] ): array {
		$search = isset( $arguments['search'] )
			? sanitize_text_field( wp_unslash( $arguments['search'] ) )
			: '';

		$mime_type = isset( $arguments['mime_type'] )
			? sanitize_mime_type( wp_unslash( $arguments['mime_type'] ) )
			: '';

		$limit = isset( $arguments['limit'] ) ? absint( $arguments['limit'] ) : 10;
		$limit = min( max( $limit, 1 ), 20 );

		$args = [
			'post_type'              => 'attachment',
			'post_status'            => 'inherit',
			's'                      => $search,
			'posts_per_page'         => $limit,
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		];

		if ( '' !== $mime_type ) {
			$args['post_mime_type'] = $mime_type;
		}

		$query     = new \WP_Query( $args );
		$formatter = new MediaFormatter();
		$items     = [];

		foreach ( $query->posts as $attachment ) {
			$items[] = $formatter->summary( $attachment );
		}

		return ToolResponse::json(
			[
				'query'   => [
					'search'    => $search,
					'mime_type' => $mime_type,
					'limit'     => $limit,
				],
				'results' => $items,
			]
		);
	}
	
	public function annotations(): array {
		return [
			'title'           => 'Search WP media attachments.',
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false,
		];
	}

	public function output_schema(): ?array {
		return [
			'type'       => 'object',
			'required'   => [ 'query', 'results' ],
			'properties' => [
				'query'   => [
					'type'       => 'object',
					'required'   => [ 'search', 'mime_type', 'limit' ],
					'properties' => [
						'search'    => [ 'type' => 'string' ],
						'mime_type' => [ 'type' => 'string' ],
						'limit'     => [ 'type' => 'integer' ],
					],
				],

				'results' => [
					'type'  => 'array',
					'items' => MediaFormatter::summary_schema(),
				],
			],
		];
	}
	
}