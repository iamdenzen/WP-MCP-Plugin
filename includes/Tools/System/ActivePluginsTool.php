<?php
namespace WP_MCP_Server\Tools\System;

use WP_MCP_Server\Tools\Contracts\ToolInterface;
use WP_MCP_Server\Tools\ToolResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ActivePluginsTool implements ToolInterface {

	public function name(): string {
		return 'wp_list_active_plugins';
	}

	public function description(): string {
		return 'Lists active WordPress plugins with safe read-only metadata.';
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
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$active_plugins = (array) get_option( 'active_plugins', [] );
		$network_active = is_multisite()
			? array_keys( (array) get_site_option( 'active_sitewide_plugins', [] ) )
			: [];

		$plugin_files = array_values(
			array_unique(
				array_merge( $active_plugins, $network_active )
			)
		);

		$plugins = [];

		foreach ( $plugin_files as $plugin_file ) {
			$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;

			if ( ! file_exists( $plugin_path ) ) {
				continue;
			}

			$data = get_plugin_data( $plugin_path, false, false );

			$plugins[] = [
				'name'           => $data['Name'] ?? '',
				'plugin_file'    => $plugin_file,
				'version'        => $data['Version'] ?? '',
				'author'         => wp_strip_all_tags( $data['Author'] ?? '' ),
				'description'    => wp_strip_all_tags( $data['Description'] ?? '' ),
				'plugin_uri'     => esc_url_raw( $data['PluginURI'] ?? '' ),
				'network_active' => in_array( $plugin_file, $network_active, true ),
			];
		}

		return ToolResponse::json(
			[
				'count'   => count( $plugins ),
				'plugins' => $plugins,
			]
		);
	}
	
	public function annotations(): array {
		return [
			'title'           => 'List WP Active Plugins',
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false,
		];
	}

	public function output_schema(): ?array {
		return [
			'type'       => 'object',
			'required'   => [ 'count', 'plugins' ],
			'properties' => [
				'count' => [
					'type' => 'integer',
				],
				'plugins' => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'required'   => [
							'name',
							'plugin_file',
							'version',
							'author',
							'description',
							'plugin_uri',
							'network_active',
						],
						'properties' => [
							'name' => [
								'type' => 'string',
							],
							'plugin_file' => [
								'type' => 'string',
							],
							'version' => [
								'type' => 'string',
							],
							'author' => [
								'type' => 'string',
							],
							'description' => [
								'type' => 'string',
							],
							'plugin_uri' => [
								'type' => 'string',
							],
							'network_active' => [
								'type' => 'boolean',
							],
						],
					],
				],
			],
		];
	}
}