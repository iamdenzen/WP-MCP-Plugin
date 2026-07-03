<?php

namespace WP_MCP_Server\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AdminMenu {

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
	}

	public function register_menu(): void {
		add_menu_page(
			'MCP Server',
			'MCP Server',
			'manage_options',
			'wp-mcp-server',
			[ $this, 'render_dashboard' ],
			'dashicons-rest-api',
			58
		);
	}

	public function render_dashboard(): void {
		?>
		<div class="wrap">
			<h1>MCP Server</h1>
			<p>Manage MCP OAuth clients, connected apps, and REST API access tokens.</p>
		</div>
		<?php
	}
}