<?php

namespace WP_MCP_Server\HTTP\Controllers;

use DateTimeImmutable;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use League\OAuth2\Server\Exception\OAuthServerException;
use WP_MCP_Server\Auth\OAuth\Entities\UserEntity;
use WP_MCP_Server\Auth\OAuth\OAuthServerFactory;
use WP_MCP_Server\Auth\OAuth\ScopeRegistry;
//use WP_MCP_Server\Auth\OAuth\Repositories\GrantRepository;
use WP_MCP_Server\Utilities\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OAuthAuthorizeController {

	public function handle(): void {
		if ( ! is_user_logged_in() ) {
			auth_redirect();
			exit;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Only administrators can authorize MCP access.', 'wp-mcp-server' ) );
		}

		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			$this->approve();
			return;
		}

		$this->render_consent_screen();
	}

	private function render_consent_screen(): void {
		$client_id    = isset( $_GET['client_id'] ) ? sanitize_text_field( wp_unslash( $_GET['client_id'] ) ) : '';
		$redirect_uri = isset( $_GET['redirect_uri'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_uri'] ) ) : '';
		$scope_string = isset( $_GET['scope'] ) ? sanitize_text_field( wp_unslash( $_GET['scope'] ) ) : '';
		$state        = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';

		$requested_scopes = array_filter( preg_split( '/\s+/', $scope_string ) ?: [] );
		$requested_scopes = ScopeRegistry::filter_valid( $requested_scopes );
		$all_scopes       = ScopeRegistry::all();

		Logger::oauth( 'Authorization screen rendered', [
			'client_id'    => $client_id,
			'redirect_uri' => $redirect_uri,
			'scopes'       => $requested_scopes,
			'user_id'      => get_current_user_id(),
		] );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?php esc_html_e( 'Authorize MCP Access', 'wp-mcp-server' ); ?></title>
		<?php wp_admin_css( 'login', true ); ?>

		<style>
			body.login{background:#f6f7f7}.wp-mcp-consent{width:560px;max-width:calc(100% - 32px);margin:60px auto}.wp-mcp-card{background:#fff;border:1px solid #dcdcde;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.06);padding:28px}.wp-mcp-title{margin:0 0 8px;font-size:24px;line-height:1.3;color:#1d2327}.wp-mcp-subtitle{margin:0 0 24px;color:#646970;font-size:14px}.wp-mcp-section{margin-top:22px;padding-top:18px;border-top:1px solid #f0f0f1}.wp-mcp-label{display:block;margin-bottom:6px;font-weight:600;color:#1d2327}.wp-mcp-code{display:block;background:#f6f7f7;border:1px solid #dcdcde;border-radius:6px;padding:10px 12px;word-break:break-all;font-size:12px}.wp-mcp-scopes{margin:12px 0 0;padding:0;list-style:none}.wp-mcp-scopes li{margin-bottom:10px;padding:12px;background:#f6f7f7;border-radius:6px}.wp-mcp-scope-name{display:block;margin-bottom:4px;font-weight:600}.wp-mcp-scope-desc{color:#646970;font-size:13px}.wp-mcp-actions{display:flex;gap:12px;align-items:center;margin-top:28px}.wp-mcp-actions .button-primary{min-height:38px;padding:4px 18px}.wp-mcp-cancel{color:#646970;text-decoration:none}.wp-mcp-cancel:hover{color:#135e96}
		</style>
	</head>

	<body class="login">
		<div class="wp-mcp-consent">
			<div class="wp-mcp-card">
				<h1 class="wp-mcp-title">
					<?php esc_html_e( 'Authorize MCP Access', 'wp-mcp-server' ); ?>
				</h1>

				<p class="wp-mcp-subtitle">
					<?php esc_html_e( 'An external app is requesting permission to access this WordPress site through MCP.', 'wp-mcp-server' ); ?>
				</p>

				<form method="post">
					<?php wp_nonce_field( 'wp_mcp_oauth_authorize' ); ?>

					<input type="hidden" name="approve" value="1">

					<?php foreach ( $_GET as $key => $value ) : ?>
					<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( wp_unslash( $value ) ); ?>">
					<?php endforeach; ?>

					<div class="wp-mcp-section">
						<span class="wp-mcp-label"><?php esc_html_e( 'Client ID', 'wp-mcp-server' ); ?></span>
						<code class="wp-mcp-code"><?php echo esc_html( $client_id ); ?></code>
					</div>

					<div class="wp-mcp-section">
						<span class="wp-mcp-label"><?php esc_html_e( 'Redirect URI', 'wp-mcp-server' ); ?></span>
						<code class="wp-mcp-code"><?php echo esc_html( $redirect_uri ); ?></code>
					</div>

					<div class="wp-mcp-section">
						<span class="wp-mcp-label"><?php esc_html_e( 'Requested permissions', 'wp-mcp-server' ); ?></span>

						<ul class="wp-mcp-scopes">
							<?php foreach ( $requested_scopes as $scope ) : ?>
							<li>
								<code class="wp-mcp-scope-name"><?php echo esc_html( $scope ); ?></code>
								<span class="wp-mcp-scope-desc">
									<?php echo esc_html( $all_scopes[ $scope ] ?? '' ); ?>
								</span>
							</li>
							<?php endforeach; ?>
						</ul>
					</div>

					<div class="wp-mcp-actions">
						<button type="submit" class="button button-primary button-large">
							<?php esc_html_e( 'Approve Access', 'wp-mcp-server' ); ?>
						</button>

						<a class="wp-mcp-cancel" href="<?php echo esc_url( admin_url() ); ?>">
							<?php esc_html_e( 'Cancel', 'wp-mcp-server' ); ?>
						</a>
					</div>
				</form>
			</div>
		</div>
	</body>
</html>
<?php
	}

	private function approve(): void {
		check_admin_referer( 'wp_mcp_oauth_authorize' );

		$request  = ServerRequestFactory::fromGlobals();
		$response = new Response();

		try {
			$server      = OAuthServerFactory::authorization_server();
			$authRequest = $server->validateAuthorizationRequest( $request );
			
			/*$scopes = array_map(
				static fn( $scope ) => $scope->getIdentifier(),
				$authRequest->getScopes()
			);

			$grant_id = ( new GrantRepository() )->create_or_update(
				$authRequest->getClient()->getIdentifier(),
				get_current_user_id(),
				$scopes
			);

			update_user_meta(
				get_current_user_id(),
				'wp_mcp_current_oauth_grant_id',
				$grant_id
			);*/

			$authRequest->setUser( new UserEntity( get_current_user_id() ) );
			$authRequest->setAuthorizationApproved( true );

			$response = $server->completeAuthorizationRequest( $authRequest, $response );

			Logger::oauth( 'Authorization request approved', [
				'user_id' => get_current_user_id(),
			] );
			
			$this->emit_response( $response );
			
		} catch ( OAuthServerException $exception ) {
			
			Logger::oauth( 'Authorization request failed', [
				'error' => $exception->getMessage(),
			], 'warning' );

			$this->emit_response( $exception->generateHttpResponse( $response ) );
			
		} catch ( \Throwable $exception ) {
			
			Logger::oauth( 'Authorization request failed', [
				'error' => $exception->getMessage(),
			], 'warning' );
			
			status_header( 500 );
			wp_die( esc_html( $exception->getMessage() ) );
			
		}
	}

	private function emit_response( Response $response ): void {
		status_header( $response->getStatusCode() );

		foreach ( $response->getHeaders() as $name => $values ) {
			foreach ( $values as $value ) {
				header( $name . ': ' . $value, false );
			}
		}

		echo (string) $response->getBody();
		exit;
	}
}