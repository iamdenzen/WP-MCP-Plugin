<?php

namespace WP_MCP_Server\Auth\OAuth\Repositories;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use WP_MCP_Server\Auth\OAuth\Entities\ScopeEntity;
use WP_MCP_Server\Auth\OAuth\ScopeRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ScopeRepository implements ScopeRepositoryInterface {

	public function getScopeEntityByIdentifier( $identifier ): ?ScopeEntity {
		$identifier = (string) $identifier;

		if ( ! ScopeRegistry::exists( $identifier ) ) {
			return null;
		}

		return new ScopeEntity( $identifier );
	}

	public function finalizeScopes(
		array $scopes,
		$grantType,
		ClientEntityInterface $clientEntity,
		$userIdentifier = null
	): array {
		global $wpdb;

		$table = $wpdb->prefix . 'mcp_oauth_clients';

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT scopes FROM {$table} WHERE client_id = %s AND active = 1 LIMIT 1",
				$clientEntity->getIdentifier()
			)
		);

		if ( ! $row ) {
			return [];
		}

		$allowed = json_decode( (string) $row->scopes, true );
		$allowed = is_array( $allowed ) ? $allowed : [];

		return array_values(
			array_filter(
				$scopes,
				static function ( $scope ) use ( $allowed ): bool {
					return in_array( $scope->getIdentifier(), $allowed, true );
				}
			)
		);
	}
}