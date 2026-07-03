<?php

namespace WP_MCP_Server\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OAuthStatusPage {

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_post_wp_mcp_save_oauth_status_settings', [ $this, 'save_settings' ] );
	}

	public function add_menu_page(): void {
		add_submenu_page(
			'wp-mcp-server',
			'MCP OAuth Status',
			'MCP OAuth Status',
			'manage_options',
			'wp-mcp-oauth-status',
			[ $this, 'render' ]
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'wp-mcp-server' ) );
		}

		global $wpdb;

		$clients_table        = $wpdb->prefix . 'mcp_oauth_clients';
		$auth_codes_table     = $wpdb->prefix . 'mcp_oauth_auth_codes';
		$access_tokens_table  = $wpdb->prefix . 'mcp_oauth_access_tokens';
		$refresh_tokens_table = $wpdb->prefix . 'mcp_oauth_refresh_tokens';

		$counts = [
			'clients'        => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$clients_table}" ),
			'active_clients' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$clients_table} WHERE active = 1" ),
			'access_tokens'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$access_tokens_table}" ),
			'revoked_tokens' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$access_tokens_table} WHERE revoked = 1" ),
			'refresh_tokens' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$refresh_tokens_table}" ),
			'auth_codes'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$auth_codes_table}" ),
		];

		$logging_enabled = (bool) get_option( 'wp_mcp_server_logging_enabled', false );
		$logging_level   = (string) get_option( 'wp_mcp_server_logging_level', 'info' );
		$log_lines       = $this->get_recent_oauth_logs();

		?>
		<div class="wrap">
			<h1>MCP OAuth Status</h1>

			<h2>Endpoints</h2>
			<table class="widefat striped">
				<tbody>
					<?php foreach ( $this->endpoints() as $label => $url ) : ?>
						<tr>
							<th style="width:260px;"><?php echo esc_html( $label ); ?></th>
							<td><code><?php echo esc_html( $url ); ?></code></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<h2>OAuth Summary</h2>
			<table class="widefat striped">
				<tbody>
					<?php foreach ( $counts as $label => $count ) : ?>
						<tr>
							<th style="width:260px;"><?php echo esc_html( ucwords( str_replace( '_', ' ', $label ) ) ); ?></th>
							<td><?php echo esc_html( (string) $count ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<h2>Logging</h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'wp_mcp_save_oauth_status_settings' ); ?>
				<input type="hidden" name="action" value="wp_mcp_save_oauth_status_settings">

				<table class="form-table">
					<tr>
						<th>Enable logging</th>
						<td>
							<label>
								<input type="checkbox" name="logging_enabled" value="1" <?php checked( $logging_enabled ); ?>>
								Enable OAuth/MCP debug logs
							</label>
						</td>
					</tr>

					<tr>
						<th>Minimum log level</th>
						<td>
							<select name="logging_level">
								<?php foreach ( [ 'debug', 'info', 'warning', 'error' ] as $level ) : ?>
									<option value="<?php echo esc_attr( $level ); ?>" <?php selected( $logging_level, $level ); ?>>
										<?php echo esc_html( strtoupper( $level ) ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>

				<?php submit_button( 'Save Logging Settings' ); ?>
			</form>

			<h2>Recent OAuth Logs</h2>

			<?php if ( empty( $log_lines ) ) : ?>
				<p>No OAuth logs found.</p>
			<?php else : ?>
				<textarea readonly style="width:100%;min-height:260px;font-family:monospace;"><?php echo esc_textarea( implode( '', $log_lines ) ); ?></textarea>
			<?php endif; ?>
		</div>
		<?php
	}

	public function save_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'wp-mcp-server' ) );
		}

		check_admin_referer( 'wp_mcp_save_oauth_status_settings' );

		update_option(
			'wp_mcp_server_logging_enabled',
			isset( $_POST['logging_enabled'] ),
			false
		);

		$level = isset( $_POST['logging_level'] )
			? sanitize_text_field( wp_unslash( $_POST['logging_level'] ) )
			: 'info';

		if ( ! in_array( $level, [ 'debug', 'info', 'warning', 'error' ], true ) ) {
			$level = 'info';
		}

		update_option( 'wp_mcp_server_logging_level', $level, false );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'wp-mcp-oauth-status',
					'updated' => '1',
				],
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	private function endpoints(): array {
		return [
			'MCP Endpoint'                  => home_url( '/mcp' ),
			'Protected Resource Metadata'  => home_url( '/.well-known/oauth-protected-resource' ),
			'Authorization Server Metadata'=> home_url( '/.well-known/oauth-authorization-server' ),
			'Authorize Endpoint'           => home_url( '/authorize' ),
			'Token Endpoint'               => home_url( '/token' ),
		];
	}

	private function get_recent_oauth_logs(): array {
		$upload_dir = wp_upload_dir();
		$file       = trailingslashit( $upload_dir['basedir'] ) . 'wp-mcp-server/logs/oauth.log';

		if ( ! file_exists( $file ) || ! is_readable( $file ) ) {
			return [];
		}

		$lines = file( $file );

		if ( ! is_array( $lines ) ) {
			return [];
		}

		return array_slice( $lines, -80 );
	}
}