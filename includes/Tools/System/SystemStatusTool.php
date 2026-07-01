<?php

namespace WP_MCP_Server\Tools\System;

use WP_MCP_Server\Tools\Contracts\ToolInterface;
use WP_MCP_Server\Tools\ToolResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SystemStatusTool implements ToolInterface {

	public function name(): string {
		return 'wp_get_system_status';
	}

	public function description(): string {
		return 'Gets safe read-only WordPress system status and diagnostic information.';
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
		global $wpdb;

		$theme = wp_get_theme();

		$data = [
			'wordpress' => [
				'version'              => get_bloginfo( 'version' ),
				'site_url'             => site_url(),
				'home_url'             => home_url(),
				'name'                 => get_bloginfo( 'name' ),
				'description'          => get_bloginfo( 'description' ),
				'language'             => get_bloginfo( 'language' ),
				'timezone'             => wp_timezone_string(),
				'is_multisite'         => is_multisite(),
				'permalink_structure'  => get_option( 'permalink_structure' ),
				'users_can_register'   => (bool) get_option( 'users_can_register' ),
				'default_comment_status' => get_option( 'default_comment_status' ),
			],
			'environment' => [
				'php_version'          => PHP_VERSION,
				'mysql_version'        => $wpdb->db_version(),
				'server_software'      => isset( $_SERVER['SERVER_SOFTWARE'] )
					? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) )
					: '',
				'wp_memory_limit'      => WP_MEMORY_LIMIT,
				'wp_max_memory_limit'  => WP_MAX_MEMORY_LIMIT,
				'php_memory_limit'     => ini_get( 'memory_limit' ),
				'max_upload_size'      => size_format( wp_max_upload_size() ),
				'debug_mode'           => defined( 'WP_DEBUG' ) && WP_DEBUG,
				'cron_disabled'        => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
				'object_cache_enabled' => wp_using_ext_object_cache(),
				'filesystem_method'    => get_filesystem_method(),
			],
			'theme' => [
				'name'    => $theme->get( 'Name' ),
				'version' => $theme->get( 'Version' ),
				'author'  => wp_strip_all_tags( $theme->get( 'Author' ) ),
			],
			'plugins' => [
				'active_count' => count( (array) get_option( 'active_plugins', [] ) ),
			],
		];

		return ToolResponse::json($data);
	}
	
	public function annotations(): array {
		return [
			'title'           => 'Get WP System Status',
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false,
		];
	}

	public function output_schema(): ?array {
		return null;
	}
}