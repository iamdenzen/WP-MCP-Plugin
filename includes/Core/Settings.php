<?php

namespace WP_MCP_Server\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {

	public static function allowed_post_types(): array {
		$post_types = [
			'post',
			'page',
		];

		/**
		 * Filter allowed post types exposed through MCP.
		 *
		 * @param array $post_types Allowed post types.
		 */
		return apply_filters( 'wp_mcp_server_allowed_post_types', $post_types );
	}

	public static function allowed_post_statuses(): array {
		$post_statuses = [
			'publish',
			'draft',
			'trash'
		];

		/**
		 * Filter allowed post statuses exposed through MCP.
		 *
		 * @param array $post_statuses Allowed post statuses.
		 */
		return apply_filters( 'wp_mcp_server_allowed_post_statuses', $post_statuses );
	}
}