<?php

namespace WP_MCP_Server\Tools\System;

use WP_MCP_Server\Tools\Contracts\ToolInterface;
use WP_MCP_Server\Tools\ToolResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SiteSettingsTool implements ToolInterface {

	public function name(): string {
		return 'wp_get_site_settings';
	}

	public function description(): string {
		return 'Gets safe read-only WordPress site settings.';
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
		$data = [
			'site' => [
				'name'        => get_option( 'blogname' ),
				'description' => get_option( 'blogdescription' ),
				'site_url'    => site_url(),
				'home_url'    => home_url(),
				'language'    => get_bloginfo( 'language' ),
				'timezone'    => wp_timezone_string(),
			],
			'formatting' => [
				'date_format'   => get_option( 'date_format' ),
				'time_format'   => get_option( 'time_format' ),
				'start_of_week' => (int) get_option( 'start_of_week' ),
			],
			'reading' => [
				'posts_per_page'  => (int) get_option( 'posts_per_page' ),
				'show_on_front'   => get_option( 'show_on_front' ),
				'page_on_front'   => (int) get_option( 'page_on_front' ),
				'page_for_posts'  => (int) get_option( 'page_for_posts' ),
				'blog_public'     => (bool) get_option( 'blog_public' ),
			],
			'discussion' => [
				'default_comment_status' => get_option( 'default_comment_status' ),
				'default_ping_status'    => get_option( 'default_ping_status' ),
				'comments_notify'        => (bool) get_option( 'comments_notify' ),
				'moderation_notify'      => (bool) get_option( 'moderation_notify' ),
			],
			'permalinks' => [
				'structure' => get_option( 'permalink_structure' ),
			],
			'users' => [
				'users_can_register' => (bool) get_option( 'users_can_register' ),
			],
		];

		return ToolResponse::json( $data );
	}
	
	public function annotations(): array {
		return [
			'title'           => 'Get Site Settings',
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