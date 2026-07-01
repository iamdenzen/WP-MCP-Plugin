<?php

namespace WP_MCP_Server\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PostFormatter {

	public function summary( \WP_Post $post ): array {
		return [
			'id'       => $post->ID,
			'title'    => get_the_title( $post ),
			'type'     => $post->post_type,
			'url'      => get_permalink( $post ),
			'excerpt'  => wp_strip_all_tags( get_the_excerpt( $post ) ),
			'date'     => get_the_date( DATE_ATOM, $post ),
			'modified' => get_the_modified_date( DATE_ATOM, $post ),
		];
	}

	public function full( \WP_Post $post ): array {
		return [
			'id'             => $post->ID,
			'type'           => $post->post_type,
			'title'          => get_the_title( $post ),
			'slug'           => $post->post_name,
			'url'            => get_permalink( $post ),
			'excerpt'        => wp_strip_all_tags( get_the_excerpt( $post ) ),
			'content'        => wp_strip_all_tags( apply_filters( 'the_content', $post->post_content ) ),
			'date'           => get_the_date( DATE_ATOM, $post ),
			'modified'       => get_the_modified_date( DATE_ATOM, $post ),
			'featured_image' => get_the_post_thumbnail_url( $post, 'full' ) ?: null,
			'author'         => [
				'id'   => (int) $post->post_author,
				'name' => get_the_author_meta( 'display_name', $post->post_author ),
			],
			'categories'     => $this->terms( $post->ID, 'category' ),
			'tags'           => $this->terms( $post->ID, 'post_tag' ),
		];
	}

	private function terms( int $post_id, string $taxonomy ): array {
		$terms = get_the_terms( $post_id, $taxonomy );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return [];
		}

		return array_map(
			static function ( $term ): array {
				return [
					'id'   => (int) $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				];
			},
			$terms
		);
	}
	
	public static function summary_schema(): array {
		return [
			'type'       => 'object',
			'required'   => [ 'id', 'title', 'type', 'url', 'excerpt', 'date', 'modified' ],
			'properties' => [
				'id'       => [ 'type' => 'integer' ],
				'title'    => [ 'type' => 'string' ],
				'type'     => [ 'type' => 'string' ],
				'url'      => [ 'type' => [ 'string', 'null' ] ],
				'excerpt'  => [ 'type' => 'string' ],
				'date'     => [ 'type' => 'string', 'format' => 'date-time' ],
				'modified' => [ 'type' => 'string', 'format' => 'date-time' ],
			],
		];
	}

	public static function full_schema(): array {
		return [
			'type'       => 'object',
			'required'   => [
				'id',
				'type',
				'title',
				'slug',
				'url',
				'excerpt',
				'content',
				'date',
				'modified',
				'featured_image',
				'author',
				'categories',
				'tags',
			],
			'properties' => [
				'id'             => [ 'type' => 'integer' ],
				'type'           => [ 'type' => 'string' ],
				'title'          => [ 'type' => 'string' ],
				'slug'           => [ 'type' => 'string' ],
				'url'            => [ 'type' => [ 'string', 'null' ] ],
				'excerpt'        => [ 'type' => 'string' ],
				'content'        => [ 'type' => 'string' ],
				'date'           => [ 'type' => 'string', 'format' => 'date-time' ],
				'modified'       => [ 'type' => 'string', 'format' => 'date-time' ],
				'featured_image' => [ 'type' => [ 'string', 'null' ] ],
				'author'         => self::author_schema(),
				'categories'     => [
					'type'  => 'array',
					'items' => self::term_schema(),
				],
				'tags'           => [
					'type'  => 'array',
					'items' => self::term_schema(),
				],
			],
		];
	}

	public static function author_schema(): array {
		return [
			'type'       => 'object',
			'required'   => [ 'id', 'name' ],
			'properties' => [
				'id'   => [ 'type' => 'integer' ],
				'name' => [ 'type' => 'string' ],
			],
		];
	}

	public static function term_schema(): array {
		return [
			'type'       => 'object',
			'required'   => [ 'id', 'name', 'slug' ],
			'properties' => [
				'id'   => [ 'type' => 'integer' ],
				'name' => [ 'type' => 'string' ],
				'slug' => [ 'type' => 'string' ],
			],
		];
	}
}