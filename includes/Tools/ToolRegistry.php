<?php

namespace WP_MCP_Server\Tools;

use WP_MCP_Server\Tools\Contracts\ToolInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ToolRegistry {

	/**
	 * @var array<string, ToolInterface>
	 */
	private array $tools = [];

	public function register( ToolInterface $tool ): void {
		$this->tools[ $tool->name() ] = $tool;
	}

	public function all(): array {
		return $this->tools;
	}

	public function has( string $name ): bool {
		return isset( $this->tools[ $name ] );
	}

	public function get( string $name ): ?ToolInterface {
		return $this->tools[ $name ] ?? null;
	}

	public function to_mcp_list(): array {
		$tools = [];

		foreach ( $this->tools as $tool ) {
			$item = [
				'name'        => $tool->name(),
				'description' => $tool->description(),
				'inputSchema' => $tool->input_schema(),
				'annotations' => $tool->annotations(),
			];

			$output_schema = $tool->output_schema();

			if ( null !== $output_schema ) {
				$item['outputSchema'] = $output_schema;
			}

			$tools[] = $item;
		}

		return $tools;
	}
	
	public function register_external_tools(): void {
		/**
		 * Fires before default MCP tools are registered.
		 */
		do_action( 'wp_mcp_server_before_register_tools', $this );

		/**
		 * Allow developers to register custom MCP tools.
		 *
		 * @param ToolRegistry $registry
		 */
		do_action( 'wp_mcp_server_register_tools', $this );

		/**
		 * Fires after MCP tools are registered.
		 */
		do_action( 'wp_mcp_server_after_register_tools', $this );
	}
}