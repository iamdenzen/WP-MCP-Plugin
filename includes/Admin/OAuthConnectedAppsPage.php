<?php

namespace WP_MCP_Server\Admin;

use WP_MCP_Server\Auth\OAuth\Repositories\GrantRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OAuthConnectedAppsPage {

	public function register(): void {
        add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
        add_action( 'admin_post_wp_mcp_revoke_oauth_grant', [ $this, 'handle_revoke' ] );
    }

	public function add_menu_page(): void {
		add_submenu_page(
			'wp-mcp-server',
			'MCP Connected Apps',
			'MCP Connected Apps',
			'manage_options',
			'wp-mcp-connected-apps',
			[ $this, 'render' ]
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'wp-mcp-server' ) );
		}

		global $wpdb;

		$grants_table  = $wpdb->prefix . 'mcp_oauth_grants';
		$clients_table = $wpdb->prefix . 'mcp_oauth_clients';

		$grants = $wpdb->get_results(
			"SELECT g.*, c.name AS client_name
			FROM {$grants_table} g
			LEFT JOIN {$clients_table} c ON c.client_id = g.client_id
			ORDER BY g.created_at DESC"
		);

		?>
		<div class="wrap">
			<h1>MCP Connected Apps</h1>

			<table class="widefat striped">
				<thead>
					<tr>
						<th>App</th>
						<th>User</th>
						<th>Scopes</th>
						<th>Connected</th>
						<th>Last Used</th>
						<th>Status</th>
                        <th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $grants ) ) : ?>
						<tr>
							<td colspan="6">No connected apps yet.</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $grants as $grant ) : ?>
							<?php
							$user   = get_user_by( 'id', (int) $grant->user_id );
							$scopes = json_decode( (string) $grant->scopes, true );
							$scopes = is_array( $scopes ) ? $scopes : [];
							?>
							<tr>
								<td>
									<strong><?php echo esc_html( $grant->client_name ?: $grant->client_id ); ?></strong><br>
									<code><?php echo esc_html( $grant->client_id ); ?></code>
								</td>

								<td>
									<?php echo $user ? esc_html( $user->display_name ) : 'Unknown user'; ?>
								</td>

								<td>
									<?php foreach ( $scopes as $scope ) : ?>
										<code><?php echo esc_html( $scope ); ?></code><br>
									<?php endforeach; ?>
								</td>

								<td>
									<?php echo esc_html( $grant->created_at ); ?>
								</td>

								<td>
									<?php echo $grant->last_used_at ? esc_html( $grant->last_used_at ) : 'Never'; ?>
								</td>

								<td>
									<?php if ( $grant->revoked_at ) : ?>
										<span style="color:#b32d2e;">Revoked</span>
									<?php else : ?>
										<span style="color:#008a20;">Connected</span>
									<?php endif; ?>
								</td>
                                <td>
                                    <?php if ( ! $grant->revoked_at ) : ?>
                                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                            <?php wp_nonce_field( 'wp_mcp_revoke_oauth_grant_' . (int) $grant->id ); ?>
                                            <input type="hidden" name="action" value="wp_mcp_revoke_oauth_grant">
                                            <input type="hidden" name="grant_id" value="<?php echo esc_attr( (string) $grant->id ); ?>">

                                            <?php submit_button( 'Revoke Access', 'delete small', 'submit', false ); ?>
                                        </form>
                                    <?php else : ?>
                                        —
                                    <?php endif; ?>
                                </td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
    
    public function handle_revoke(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access denied.', 'wp-mcp-server' ) );
        }

        $grant_id = isset( $_POST['grant_id'] ) ? absint( $_POST['grant_id'] ) : 0;

        check_admin_referer( 'wp_mcp_revoke_oauth_grant_' . $grant_id );

        ( new GrantRepository() )->revoke( $grant_id, get_current_user_id() );

        wp_safe_redirect(
            add_query_arg(
                [
                    'page'    => 'wp-mcp-connected-apps',
                    'revoked' => '1',
                ],
                admin_url( 'options-general.php' )
            )
        );
        exit;
    }
}