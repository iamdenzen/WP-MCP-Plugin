<?php

namespace WP_MCP_Server\Tools;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ToolResponse {

	public static function text( string $text ): array {
		return [
			'content' => [
				[
					'type' => 'text',
					'text' => $text,
				],
			],
		];
	}

	public static function json( array $data ): array {
		return self::text(
			wp_json_encode(
				$data,
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
			)
		);
	}

	public static function error( string $message ): array {
		return [
			'isError' => true,
			'content' => [
				[
					'type' => 'text',
					'text' => $message,
				],
			],
		];
	}
}