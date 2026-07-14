<?php
namespace WP_MCP_Server\Services;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class MediaFormatter {
	public function summary( \WP_Post $attachment, bool $include_binary = false ): array {
		$data = [
			'id'          => $attachment->ID,
			'title'       => get_the_title( $attachment ),
			'mime_type'   => get_post_mime_type( $attachment ),
			'url'         => wp_get_attachment_url( $attachment->ID ),
			'alt'         => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
			'date'        => get_the_date( DATE_ATOM, $attachment ),
			'modified'    => get_the_modified_date( DATE_ATOM, $attachment ),
		];

		if ( $include_binary ) {
			$data['binary'] = $this->binary( $attachment->ID );
		}

		return $data;
	}

	private function binary( int $attachment_id ): ?array {
		// Only images have a 'thumbnail' intermediate size; other mime types
		// (pdf, video, audio, etc.) fall back to the original file.
		if ( wp_attachment_is_image( $attachment_id ) ) {
			$file_path = $this->attachment_file_path_for_size( $attachment_id, 'thumbnail' );
		} else {
			$file_path = get_attached_file( $attachment_id );
		}

		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return null;
		}

		$contents = file_get_contents( $file_path );

		if ( false === $contents ) {
			return null;
		}

		$mime_type = wp_check_filetype( $file_path )['type'] ?? get_post_mime_type( $attachment_id );

		return [
			'mime_type' => $mime_type ?: 'application/octet-stream',
			'base64'    => base64_encode( $contents ),
		];
	}

	/**
	 * Resolves the on-disk path for a given intermediate image size,
	 * falling back to the original file if that size doesn't exist.
	 */
	private function attachment_file_path_for_size( int $attachment_id, string $size ): ?string {
		$original_path = get_attached_file( $attachment_id );

		if ( ! $original_path ) {
			return null;
		}

		$metadata = wp_get_attachment_metadata( $attachment_id );

		if ( empty( $metadata['sizes'][ $size ]['file'] ) ) {
			return $original_path;
		}

		return path_join( dirname( $original_path ), $metadata['sizes'][ $size ]['file'] );
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
				'binary'    => [
					'type'       => [ 'object', 'null' ],
					'properties' => [
						'mime_type' => [ 'type' => 'string' ],
						'base64'    => [ 'type' => 'string' ],
					],
				],
			],
		];
	}
}