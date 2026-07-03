<?php

namespace WP_MCP_Server\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RestApiTokenPage {

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_post_wp_mcp_generate_rest_token', [ $this, 'handle_generate' ] );
		add_action( 'admin_post_wp_mcp_revoke_rest_token', [ $this, 'handle_revoke' ] );
	}

	public function add_menu_page(): void {
		add_submenu_page(
			'wp-mcp-server',
			'MCP REST API Token',
			'MCP REST API Token',
			'manage_options',
			'wp-mcp-rest-api-token',
			[ $this, 'render' ]
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'wp-mcp-server' ) );
		}

		$has_token    = (bool) get_option( 'wp_mcp_server_rest_api_token_hash', '' );
		$created_at   = (string) get_option( 'wp_mcp_server_rest_api_token_created_at', '' );
		$last_used_at = (string) get_option( 'wp_mcp_server_rest_api_token_last_used_at', '' );
		$new_token    = isset( $_GET['new_token'] ) ? sanitize_text_field( wp_unslash( $_GET['new_token'] ) ) : '';
		?>
		<div class="wrap">
			<h1>MCP REST API Token</h1>

			<p>
				Use this token for Make.com, Postman, or custom REST API integrations.
				Do not use this for the main MCP connector. The main <code>/mcp</code> endpoint should stay OAuth-only.
			</p>

			<?php if ( $new_token ) : ?>
				<div class="notice notice-success">
					<p><strong>New REST API Token:</strong></p>
					<input type="text" readonly value="<?php echo esc_attr( $new_token ); ?>" class="large-text code">
					<p><strong>Copy this now.</strong> It will not be shown again.</p>
				</div>
			<?php endif; ?>

			<table class="widefat striped" style="max-width: 800px;">
				<tbody>
					<tr>
						<th>Status</th>
						<td>
							<?php if ( $has_token ) : ?>
								<span style="color:#008a20;">Active</span>
							<?php else : ?>
								<span style="color:#b32d2e;">No token generated</span>
							<?php endif; ?>
						</td>
					</tr>

					<tr>
						<th>Created</th>
						<td><?php echo $created_at ? esc_html( $created_at ) : '—'; ?></td>
					</tr>

					<tr>
						<th>Last Used</th>
						<td><?php echo $last_used_at ? esc_html( $last_used_at ) : 'Never'; ?></td>
					</tr>
				</tbody>
			</table>

			<p></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;">
				<?php wp_nonce_field( 'wp_mcp_generate_rest_token' ); ?>
				<input type="hidden" name="action" value="wp_mcp_generate_rest_token">
				<?php submit_button( $has_token ? 'Regenerate Token' : 'Generate Token', 'primary', 'submit', false ); ?>
			</form>

			<?php if ( $has_token ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-left:8px;">
					<?php wp_nonce_field( 'wp_mcp_revoke_rest_token' ); ?>
					<input type="hidden" name="action" value="wp_mcp_revoke_rest_token">
					<?php submit_button( 'Revoke Token', 'delete', 'submit', false ); ?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	public function handle_generate(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'wp-mcp-server' ) );
		}

		check_admin_referer( 'wp_mcp_generate_rest_token' );

		$token = 'wpmcp_' . wp_generate_password( 48, false, false );

		update_option( 'wp_mcp_server_rest_api_token_hash', wp_hash_password( $token ), false );
		update_option( 'wp_mcp_server_rest_api_token_created_at', current_time( 'mysql', true ), false );
		delete_option( 'wp_mcp_server_rest_api_token_last_used_at' );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'      => 'wp-mcp-rest-api-token',
					'new_token' => rawurlencode( $token ),
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	public function handle_revoke(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'wp-mcp-server' ) );
		}

		check_admin_referer( 'wp_mcp_revoke_rest_token' );

		delete_option( 'wp_mcp_server_rest_api_token_hash' );
		delete_option( 'wp_mcp_server_rest_api_token_created_at' );
		delete_option( 'wp_mcp_server_rest_api_token_last_used_at' );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'wp-mcp-rest-api-token',
					'revoked' => '1',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}