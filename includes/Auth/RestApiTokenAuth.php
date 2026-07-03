<?php

namespace WP_MCP_Server\Auth;

use WP_REST_Request;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RestApiTokenAuth {

	public static function verify( WP_REST_Request $request ) {
		$token_hash = (string) get_option( 'wp_mcp_server_rest_api_token_hash', '' );

		if ( '' === $token_hash ) {
			return new WP_Error(
				'wp_mcp_rest_token_missing',
				'REST API token is not configured.',
				[ 'status' => 401 ]
			);
		}

		$header = $request->get_header( 'authorization' );

		if ( ! $header || ! str_starts_with( $header, 'Bearer ' ) ) {
			return new WP_Error(
				'wp_mcp_rest_token_required',
				'Bearer token required.',
				[ 'status' => 401 ]
			);
		}

		$given_token = trim( substr( $header, 7 ) );

		if ( ! wp_check_password( $given_token, $token_hash ) ) {
			return new WP_Error(
				'wp_mcp_rest_token_invalid',
				'Invalid bearer token.',
				[ 'status' => 401 ]
			);
		}

		update_option( 'wp_mcp_server_rest_api_token_last_used_at', current_time( 'mysql', true ), false );

		return true;
	}
}