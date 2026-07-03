<?php

namespace WP_MCP_Server\Admin;

use WP_MCP_Server\Auth\OAuth\ScopeRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OAuthClientsPage {

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_post_wp_mcp_create_oauth_client', [ $this, 'handle_create_client' ] );
	}

	public function add_menu_page(): void {
		add_submenu_page(
			'wp-mcp-server',
			'MCP OAuth Clients',
			'MCP OAuth Clients',
			'manage_options',
			'wp-mcp-oauth-clients',
			[ $this, 'render' ]
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'wp-mcp-server' ) );
		}

		global $wpdb;

		$table   = $wpdb->prefix . 'mcp_oauth_clients';
		$clients = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC" );
		$url_client_id  = isset( $_GET['client_id'] ) ? sanitize_text_field( wp_unslash( $_GET['client_id'] ) ) : '';
		$url_secret  = isset( $_GET['client_secret'] ) ? sanitize_text_field( wp_unslash( $_GET['client_secret'] ) ) : '';
		?>
		<div class="wrap">
			<h1>MCP OAuth Clients</h1>

			<?php if ( $url_secret ) : ?>
				<div class="notice notice-success">
					<p><strong>Client ID:</strong></p>
					<input type="text" readonly value="<?php echo esc_attr( $url_client_id ); ?>" style="width: 500px;">
					<p><strong>Client Secret:</strong></p>
					<input type="text" readonly value="<?php echo esc_attr( $url_secret ); ?>" style="width: 500px;">
					<p><strong>Copy this now.</strong> It will not be shown again.</p>
				</div>
			<?php endif; ?>

			<h2>Create Client</h2>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'wp_mcp_create_oauth_client' ); ?>
				<input type="hidden" name="action" value="wp_mcp_create_oauth_client">

				<table class="form-table">
					<tr>
						<th><label for="name">Name</label></th>
						<td>
							<input name="name" id="name" type="text" class="regular-text" required placeholder="Claude">
						</td>
					</tr>

					<tr>
						<th><label for="redirect_uri">Redirect URI</label></th>
						<td>
							<input name="redirect_uri" id="redirect_uri" type="url" class="large-text" required value="https://claude.ai/api/mcp/auth_callback">
						</td>
					</tr>

					<tr>
						<th>Scopes</th>
						<td>
							<?php foreach ( ScopeRegistry::all() as $scope => $description ) : ?>
								<label style="display:block;margin-bottom:6px;">
									<input type="checkbox" name="scopes[]" value="<?php echo esc_attr( $scope ); ?>">
									<strong><?php echo esc_html( $scope ); ?></strong>
									— <?php echo esc_html( $description ); ?>
								</label>
							<?php endforeach; ?>
						</td>
					</tr>
				</table>

				<?php submit_button( 'Create OAuth Client' ); ?>
			</form>

			<!--<hr>

			<h2>Existing Clients</h2>

			<table class="widefat striped">
				<thead>
					<tr>
						<th>Name</th>
						<th>Client ID</th>
						<th>Redirect URIs</th>
						<th class="scope-col">Scopes</th>
						<th>Active</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $clients ) ) : ?>
						<tr>
							<td colspan="5">No OAuth clients yet.</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $clients as $client ) : ?>
							<tr>
								<td><?php echo esc_html( $client->name ); ?></td>
								<td><code><?php echo esc_html( $client->client_id ); ?></code></td>
								<td><code><?php echo esc_html( $client->redirect_uris ); ?></code></td>
								<td class="scope-col"><code><?php echo esc_html( $client->scopes ); ?></code></td>
								<td><?php echo $client->active ? 'Yes' : 'No'; ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>-->
		</div>
        <style>.scope-col{word-break:break-all;max-width:500px;}</style>
		<?php
	}

	public function handle_create_client(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'wp-mcp-server' ) );
		}

		check_admin_referer( 'wp_mcp_create_oauth_client' );

		global $wpdb;

		$name         = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$redirect_uri = isset( $_POST['redirect_uri'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_uri'] ) ) : '';
		$scopes       = isset( $_POST['scopes'] ) && is_array( $_POST['scopes'] )
			? array_map( 'sanitize_text_field', wp_unslash( $_POST['scopes'] ) )
			: [];

		$scopes = ScopeRegistry::filter_valid( $scopes );

		if ( '' === $name || '' === $redirect_uri || empty( $scopes ) ) {
			wp_die( esc_html__( 'Name, redirect URI, and at least one scope are required.', 'wp-mcp-server' ) );
		}

		$client_id     = 'wp_mcp_' . wp_generate_password( 32, false, false );
		$client_secret = wp_generate_password( 64, false, false );

		$wpdb->insert(
			$wpdb->prefix . 'mcp_oauth_clients',
			[
				'client_id'          => $client_id,
				'client_secret_hash' => wp_hash_password( $client_secret ),
				'name'               => $name,
				'redirect_uris'      => wp_json_encode( [ $redirect_uri ] ),
				'scopes'             => wp_json_encode( $scopes ),
				'active'             => 1,
				'created_by'         => get_current_user_id(),
				'created_at'         => current_time( 'mysql', true ),
				'updated_at'         => current_time( 'mysql', true ),
			],
			[
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
				'%d',
				'%s',
				'%s',
			]
		);

		wp_safe_redirect(
			add_query_arg(
				[
					'page'          => 'wp-mcp-oauth-clients',
					'client_id'     => $client_id,
					'client_secret' => rawurlencode( $client_secret ),
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}