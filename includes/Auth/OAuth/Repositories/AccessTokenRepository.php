<?php

namespace WP_MCP_Server\Auth\OAuth\Repositories;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use WP_MCP_Server\Auth\OAuth\Entities\AccessTokenEntity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AccessTokenRepository implements AccessTokenRepositoryInterface {

	public function getNewToken( ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null ): AccessTokenEntity {
		$token = new AccessTokenEntity();

		$token->setClient( $clientEntity );

		foreach ( $scopes as $scope ) {
			$token->addScope( $scope );
		}

		if ( null !== $userIdentifier ) {
			$token->setUserIdentifier( (string) $userIdentifier );
		}

		return $token;
	}

	public function persistNewAccessToken( \League\OAuth2\Server\Entities\AccessTokenEntityInterface $accessTokenEntity ): void {
		global $wpdb;
		
		$grant_id = 0;

		if ( $accessTokenEntity->getUserIdentifier() ) {
			$user_id = $accessTokenEntity->getUserIdentifier()
				? (int) $accessTokenEntity->getUserIdentifier()
				: 0;

			$scopes = array_map(
				static fn( $scope ) => $scope->getIdentifier(),
				$accessTokenEntity->getScopes()
			);

			if ( $user_id > 0 ) {
				$grant_id = ( new GrantRepository() )->create_or_update(
					$accessTokenEntity->getClient()->getIdentifier(),
					$user_id,
					$scopes
				);
			}
		}

		$wpdb->insert(
			$wpdb->prefix . 'mcp_oauth_access_tokens',
			[
				'id'         => $accessTokenEntity->getIdentifier(),
				'grant_id'	 => $grant_id ?: null,
				'client_id'  => $accessTokenEntity->getClient()->getIdentifier(),
				'user_id'    => $accessTokenEntity->getUserIdentifier() ? (int) $accessTokenEntity->getUserIdentifier() : null,
				'scopes'     => wp_json_encode(
					array_map(
						static fn( $scope ) => $scope->getIdentifier(),
						$accessTokenEntity->getScopes()
					)
				),
				'revoked'    => 0,
				'expires_at' => $accessTokenEntity->getExpiryDateTime()->format( 'Y-m-d H:i:s' ),
				'created_at' => current_time( 'mysql', true ),
			],
			[
				'%s',
				'%d',
				'%s',
				'%d',
				'%s',
				'%d',
				'%s',
				'%s',
			]
		);
		
		/*if ( $grant_id && $accessTokenEntity->getUserIdentifier() ) {
			delete_user_meta(
				(int) $accessTokenEntity->getUserIdentifier(),
				'wp_mcp_current_oauth_grant_id'
			);
		}*/
	}

	public function revokeAccessToken( $tokenId ): void {
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'mcp_oauth_access_tokens',
			[ 'revoked' => 1 ],
			[ 'id' => (string) $tokenId ],
			[ '%d' ],
			[ '%s' ]
		);
	}

	public function isAccessTokenRevoked( $tokenId ): bool {
		global $wpdb;

		$revoked = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT revoked FROM {$wpdb->prefix}mcp_oauth_access_tokens WHERE id = %s LIMIT 1",
				(string) $tokenId
			)
		);

		return null === $revoked || 1 === (int) $revoked;
	}
}