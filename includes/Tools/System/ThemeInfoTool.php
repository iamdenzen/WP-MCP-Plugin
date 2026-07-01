<?php

namespace WP_MCP_Server\Tools\System;

use WP_MCP_Server\Tools\Contracts\ToolInterface;
use WP_MCP_Server\Tools\ToolResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ThemeInfoTool implements ToolInterface {

	public function name(): string {
		return 'wp_get_theme_info';
	}

	public function description(): string {
		return 'Gets safe read-only information about the active WordPress theme.';
	}

	public function input_schema(): array {
		return [
			'type'       => 'object',
			'properties' => new \stdClass(),
		];
	}

	public function required_scopes(): array {
		return [ 'system:read' ];
	}

	public function execute( array $arguments = [] ): array {
		$theme        = wp_get_theme();
		$parent_theme = $theme->parent();

		$data = [
			'active_theme' => [
				'name'        => $theme->get( 'Name' ),
				'version'     => $theme->get( 'Version' ),
				'description' => wp_strip_all_tags( $theme->get( 'Description' ) ),
				'author'      => wp_strip_all_tags( $theme->get( 'Author' ) ),
				'author_uri'  => esc_url_raw( $theme->get( 'AuthorURI' ) ),
				'theme_uri'   => esc_url_raw( $theme->get( 'ThemeURI' ) ),
				'template'    => $theme->get_template(),
				'stylesheet'  => $theme->get_stylesheet(),
				'text_domain' => $theme->get( 'TextDomain' ),
				'is_child'    => (bool) $parent_theme,
			],
			'parent_theme' => $parent_theme
				? [
					'name'       => $parent_theme->get( 'Name' ),
					'version'    => $parent_theme->get( 'Version' ),
					'author'     => wp_strip_all_tags( $parent_theme->get( 'Author' ) ),
					'template'   => $parent_theme->get_template(),
					'stylesheet' => $parent_theme->get_stylesheet(),
				]
				: null,
			'theme_supports' => [
				'post_thumbnails'       => current_theme_supports( 'post-thumbnails' ),
				'title_tag'             => current_theme_supports( 'title-tag' ),
				'custom_logo'           => current_theme_supports( 'custom-logo' ),
				'custom_header'         => current_theme_supports( 'custom-header' ),
				'custom_background'     => current_theme_supports( 'custom-background' ),
				'html5'                 => current_theme_supports( 'html5' ),
				'menus'                 => current_theme_supports( 'menus' ),
				'widgets'               => current_theme_supports( 'widgets' ),
				'woocommerce'           => current_theme_supports( 'woocommerce' ),
				'editor_styles'         => current_theme_supports( 'editor-styles' ),
				'responsive_embeds'     => current_theme_supports( 'responsive-embeds' ),
				'wp_block_styles'       => current_theme_supports( 'wp-block-styles' ),
				'align_wide'            => current_theme_supports( 'align-wide' ),
				'automatic_feed_links'  => current_theme_supports( 'automatic-feed-links' ),
			],
		];

		return ToolResponse::json( $data );
	}
	
	public function annotations(): array {
		return [
			'title'           => 'Get WP Theme Info',
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