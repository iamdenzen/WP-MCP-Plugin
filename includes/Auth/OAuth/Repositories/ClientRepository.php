<?php

namespace WP_MCP_Server\Auth\OAuth\Repositories;

use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use WP_MCP_Server\Auth\OAuth\Entities\ClientEntity;
use WP_MCP_Server\Utilities\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ClientRepository implements ClientRepositoryInterface {

	public function getClientEntity( $clientIdentifier ): ?ClientEntity {
		global $wpdb;

		$table = $wpdb->prefix . 'mcp_oauth_clients';

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE client_id = %s AND active = 1 LIMIT 1",
				(string) $clientIdentifier
			)
		);

		if ( ! $row ) {
			return null;
		}

		$redirect_uris = json_decode( (string) $row->redirect_uris, true );
		$redirect_uri  = is_array( $redirect_uris ) && ! empty( $redirect_uris )
			? (string) $redirect_uris[0]
			: '';

		return new ClientEntity(
			(string) $row->client_id,
			(string) $row->name,
			$redirect_uri,
			true
		);
	}

	public function validateClient( $clientIdentifier, $clientSecret, $grantType ): bool {
		global $wpdb;

		$table = $wpdb->prefix . 'mcp_oauth_clients';

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT client_secret_hash FROM {$table} WHERE client_id = %s AND active = 1 LIMIT 1",
				(string) $clientIdentifier
			)
		);
		
		if ( ! $row ) {
			return false;
		}

		return wp_check_password( (string) $clientSecret, (string) $row->client_secret_hash );
	}
}