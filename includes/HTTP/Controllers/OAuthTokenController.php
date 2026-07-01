<?php

namespace WP_MCP_Server\HTTP\Controllers;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use League\OAuth2\Server\Exception\OAuthServerException;
use WP_MCP_Server\Auth\OAuth\OAuthServerFactory;
use WP_MCP_Server\Utilities\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OAuthTokenController {

	public function handle(): void {
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			Logger::oauth( 'Token endpoint rejected non-POST request', [
				'method' => $_SERVER['REQUEST_METHOD'] ?? '',
			], 'warning' );
			
			status_header( 405 );
			wp_send_json(
				[
					'error'             => 'method_not_allowed',
					'error_description' => 'Use POST.',
				]
			);
		}
		
		Logger::oauth( 'Token endpoint hit', [
			'grant_type' => isset( $_POST['grant_type'] ) ? sanitize_text_field( wp_unslash( $_POST['grant_type'] ) ) : '',
			'client_id'  => isset( $_POST['client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['client_id'] ) ) : '',
		] );

		$request  = ServerRequestFactory::fromGlobals();
		$response = new Response();

		try {
			$server   = OAuthServerFactory::authorization_server();
			
			Logger::oauth( 'Token endpoint raw debug', [
				'content_type'      => $_SERVER['CONTENT_TYPE'] ?? '',
				'auth_header_seen'  => ! empty( $_SERVER['HTTP_AUTHORIZATION'] ?? '' ),
				'post_keys'         => array_keys( $_POST ),
				'grant_type'        => $_POST['grant_type'] ?? '',
				'client_id'         => $_POST['client_id'] ?? '',
				'has_client_secret' => ! empty( $_POST['client_secret'] ?? '' ),
			], 'debug' );
			
			$response = $server->respondToAccessTokenRequest( $request, $response );
			
			Logger::oauth( 'Token endpoint succeeded', [
				'grant_type' => isset( $_POST['grant_type'] ) ? sanitize_text_field( wp_unslash( $_POST['grant_type'] ) ) : '',
				'client_id'  => isset( $_POST['client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['client_id'] ) ) : '',
				'status'     => $response->getStatusCode(),
			] );

			$this->emit_response( $response );
		} catch ( OAuthServerException $exception ) {
			Logger::oauth( 'Token endpoint OAuth error', [
				'error'      => $exception->getMessage(),
				'grant_type' => isset( $_POST['grant_type'] ) ? sanitize_text_field( wp_unslash( $_POST['grant_type'] ) ) : '',
				'client_id'  => isset( $_POST['client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['client_id'] ) ) : '',
			], 'warning' );
			
			$this->emit_response( $exception->generateHttpResponse( $response ) );
		} catch ( \Throwable $exception ) {
			Logger::oauth( 'Token endpoint server error', [
				'error' => $exception->getMessage(),
			], 'error' );
			
			status_header( 500 );
			wp_send_json(
				[
					'error'             => 'server_error',
					'error_description' => $exception->getMessage(),
				]
			);
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