<?php

namespace WP_MCP_Server\Auth\OAuth\Repositories;

use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use WP_MCP_Server\Auth\OAuth\Entities\RefreshTokenEntity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RefreshTokenRepository implements RefreshTokenRepositoryInterface {

	public function getNewRefreshToken(): RefreshTokenEntity {
		return new RefreshTokenEntity();
	}

	public function persistNewRefreshToken( \League\OAuth2\Server\Entities\RefreshTokenEntityInterface $refreshTokenEntity ): void {
		global $wpdb;

		$access_token_id = $refreshTokenEntity->getAccessToken()->getIdentifier();

		$grant_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT grant_id FROM {$wpdb->prefix}mcp_oauth_access_tokens WHERE id = %s LIMIT 1",
				$access_token_id
			)
		);

		$wpdb->insert(
			$wpdb->prefix . 'mcp_oauth_refresh_tokens',
			[
				'id'              => $refreshTokenEntity->getIdentifier(),
				'grant_id'        => $grant_id ?: null,
				'access_token_id' => $access_token_id,
				'revoked'         => 0,
				'expires_at'      => $refreshTokenEntity->getExpiryDateTime()->format( 'Y-m-d H:i:s' ),
				'created_at'      => current_time( 'mysql', true ),
			],
			[
				'%s',
				'%d',
				'%s',
				'%d',
				'%s',
				'%s',
			]
		);
	}

	public function revokeRefreshToken( $tokenId ): void {
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'mcp_oauth_refresh_tokens',
			[ 'revoked' => 1 ],
			[ 'id' => (string) $tokenId ],
			[ '%d' ],
			[ '%s' ]
		);
	}

	public function isRefreshTokenRevoked( $tokenId ): bool {
		global $wpdb;

		$revoked = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT revoked FROM {$wpdb->prefix}mcp_oauth_refresh_tokens WHERE id = %s LIMIT 1",
				(string) $tokenId
			)
		);

		return null === $revoked || 1 === (int) $revoked;
	}
}