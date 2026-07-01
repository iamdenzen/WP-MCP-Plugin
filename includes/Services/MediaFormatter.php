<?php

namespace WP_MCP_Server\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MediaFormatter {

	public function summary( \WP_Post $attachment ): array {
		return [
			'id'          => $attachment->ID,
			'title'       => get_the_title( $attachment ),
			'mime_type'   => get_post_mime_type( $attachment ),
			'url'         => wp_get_attachment_url( $attachment->ID ),
			'alt'         => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
			'date'        => get_the_date( DATE_ATOM, $attachment ),
			'modified'    => get_the_modified_date( DATE_ATOM, $attachment ),
		];
	}
	
	public static function summary_schema(): array {
		return [
			'type'       => 'object',
			'required'   => [
				'id',
				'title',
				'mime_type',
				'url',
				'alt',
				'date',
				'modified',
			],
			'properties' => [
				'id'        => [ 'type' => 'integer' ],
				'title'     => [ 'type' => 'string' ],
				'mime_type' => [ 'type' => [ 'string', 'null' ] ],
				'url'       => [ 'type' => [ 'string', 'null' ] ],
				'alt'       => [ 'type' => 'string' ],
				'date'      => [ 'type' => 'string', 'format' => 'date-time' ],
				'modified'  => [ 'type' => 'string', 'format' => 'date-time' ],
			],
		];
	}
}