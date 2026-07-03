<?php

namespace WP_MCP_Server\HTTP\Controllers;

use WP_MCP_Server\Auth\OAuth\ScopeRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OAuthMetadataController {

	public function protected_resource(): void {
		$site_url = home_url();

		status_header( 200 );
		wp_send_json(
			[
				'resource'              => home_url( '/mcp' ),
				'authorization_servers' => [
					home_url(),
				],
				'scopes_supported'      => array_keys( ScopeRegistry::all() ),
				'bearer_methods_supported' => [
					'header',
				],
			]
		);
	}

	public function authorization_server(): void {
		status_header( 200 );
		wp_send_json(
			[
				'issuer'                                => home_url(),
				'authorization_endpoint'                => home_url( '/mcp/authorize' ),
				'token_endpoint'                        => home_url( '/mcp/token' ),
				'response_types_supported'              => [
					'code',
				],
				'grant_types_supported'                 => [
					'authorization_code',
					'refresh_token',
				],
				'token_endpoint_auth_methods_supported' => [
					'client_secret_post',
					'client_secret_basic',
				],
				'scopes_supported'                      => array_keys( ScopeRegistry::all() ),
				'code_challenge_methods_supported'      => [
					'S256',
				],
			]
		);
	}
}