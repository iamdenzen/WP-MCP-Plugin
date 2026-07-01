<?php

namespace WP_MCP_Server\Auth\OAuth;

use Laminas\Diactoros\ServerRequestFactory;
use League\OAuth2\Server\Exception\OAuthServerException;
use WP_MCP_Server\Auth\OAuth\Repositories\GrantRepository;
use WP_MCP_Server\Utilities\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OAuthRequestAuth {

	public static function verify(): array {
		$request = ServerRequestFactory::fromGlobals();

		try {
			$server  = OAuthServerFactory::resource_server();
			$request = $server->validateAuthenticatedRequest( $request );
			
			$token_id = (string) $request->getAttribute( 'oauth_access_token_id' );

			global $wpdb;

			$grant_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT grant_id FROM {$wpdb->prefix}mcp_oauth_access_tokens WHERE id = %s LIMIT 1",
					$token_id
				)
			);

			if ( $grant_id > 0 ) {
				$is_revoked = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->prefix}mcp_oauth_grants WHERE id = %d AND revoked_at IS NOT NULL",
						$grant_id
					)
				);

				if ( $is_revoked ) {
					return [
						'valid' => false,
						'error' => new \RuntimeException( 'OAuth grant has been revoked.' ),
					];
				}

				( new GrantRepository() )->touch_last_used( $grant_id );
			}

			Logger::oauth( 'Resource request authenticated', [
				'client_id' => (string) $request->getAttribute( 'oauth_client_id' ),
				'grant_id'  => $grant_id,
				'user_id'   => (string) $request->getAttribute( 'oauth_user_id' ),
				'scopes'    => (array) $request->getAttribute( 'oauth_scopes', [] ),
			] );
			
			return [
				'valid'     => true,
				'token_id'  => (string) $request->getAttribute( 'oauth_access_token_id' ),
				'grant_id'  => $grant_id,
				'client_id' => (string) $request->getAttribute( 'oauth_client_id' ),
				'user_id'   => (string) $request->getAttribute( 'oauth_user_id' ),
				'scopes'    => (array) $request->getAttribute( 'oauth_scopes', [] ),
			];
		} catch ( OAuthServerException $exception ) {
			
			Logger::oauth( 'Resource request failed authentication', [
				'error' => $exception->getMessage(),
			], 'warning' );
			
			return [
				'valid' => false,
				'error' => $exception,
			];
		} catch ( \Throwable $exception ) {
			
			Logger::oauth( 'Resource request failed authentication', [
				'error' => $exception->getMessage(),
			], 'warning' );
			
			return [
				'valid' => false,
				'error' => $exception,
			];
		}
	}

	public static function unauthorized(): void {
		status_header( 401 );

		header(
			'WWW-Authenticate: Bearer resource_metadata="' .
			esc_url_raw( home_url( '/.well-known/oauth-protected-resource' ) ) .
			'"'
		);

		wp_send_json(
			[
				'error'             => 'unauthorized',
				'error_description' => 'OAuth access token required.',
			]
		);
	}
}