<?php

namespace WP_MCP_Server\Tools\WordPress;

use WP_MCP_Server\Tools\Contracts\ToolInterface;
use WP_MCP_Server\Tools\ToolResponse;
use WP_MCP_Server\Core\Settings;
use WP_MCP_Server\Services\PostFormatter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SearchPostsTool implements ToolInterface {

	public function name(): string {
		return 'wp_search_posts';
	}

	public function description(): string {
		return 'Searches published WordPress posts and pages.';
	}

	public function input_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'search' => [
					'type'        => 'string',
					'description' => 'Search keyword.',
				],
				'post_type' => [
					'type'        => 'string',
					'description' => 'Post type to search. Default: post.',
					'default'     => 'post',
				],
				'post_status'	=> [
					'type'		=> 'string',
					'description'=> 'Post status to search. possible values includes publish, draft, and trash. Default: publish.'
				],
				'limit' => [
					'type'        => 'integer',
					'description' => 'Maximum results. Default 10, max 20.',
					'default'     => 10,
				],
			],
		];
	}
	
	public function required_scopes(): array {
		return [ 'posts:read' ];
	}

	public function execute( array $arguments = [] ): array {
		$search = isset( $arguments['search'] )
			? sanitize_text_field( wp_unslash( $arguments['search'] ) )
			: '';

		$post_type = isset( $arguments['post_type'] )
			? sanitize_key( $arguments['post_type'] )
			: 'post';

		$allowed_post_types = Settings::allowed_post_types();

		if ( ! in_array( $post_type, $allowed_post_types, true ) ) {
			$post_type = 'post';
		}

		$post_status = isset( $arguments['post_status'] )
			? sanitize_key( $arguments['post_status'] )
			: 'publish';

		if( !in_array($post_status, ['publish', 'draft', 'trash']) ){
			$post_status = "publish";
		}

		$limit = isset( $arguments['limit'] )
			? absint( $arguments['limit'] )
			: 10;

		$limit = min( max( $limit, 1 ), 20 );

		$query = new \WP_Query(
			[
				'post_type'              => $post_type,
				'post_status'            => $post_status,
				's'                      => $search,
				'posts_per_page'         => $limit,
				'no_found_rows'          => true,
				'ignore_sticky_posts'    => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		$items = [];

		$formatter = new PostFormatter();
		foreach ( $query->posts as $post ) {
			$items[] = $formatter->summary( $post );
		}

		return ToolResponse::json(
			[
				'query'   => [
					'search'    => $search,
					'post_type' => $post_type,
					'limit'     => $limit,
				],
				'results' => $items,
			]
		);
	}
	
	public function annotations(): array {
		return [
			'title'           => 'Search WP Post',
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
					'required'   => [ 'search', 'post_type', 'limit' ],
					'properties' => [
						'search'    => [ 'type' => 'string' ],
						'post_type' => [ 'type' => 'string' ],
						'limit'     => [ 'type' => 'integer' ],
					],
				],

				'results' => [
					'type'  => 'array',
					'items' => PostFormatter::summary_schema(),
				],
			],
		];
	}
}