<?php

namespace WP_MCP_Server\Auth\OAuth\Repositories;

use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use WP_MCP_Server\Auth\OAuth\Entities\AuthCodeEntity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AuthCodeRepository implements AuthCodeRepositoryInterface {

	public function getNewAuthCode(): AuthCodeEntity {
		return new AuthCodeEntity();
	}

	public function persistNewAuthCode( \League\OAuth2\Server\Entities\AuthCodeEntityInterface $authCodeEntity ): void {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'mcp_oauth_auth_codes',
			[
				'id'                    => $authCodeEntity->getIdentifier(),
				'client_id'             => $authCodeEntity->getClient()->getIdentifier(),
				'user_id'               => (int) $authCodeEntity->getUserIdentifier(),
				'scopes'                => wp_json_encode(
					array_map(
						static fn( $scope ) => $scope->getIdentifier(),
						$authCodeEntity->getScopes()
					)
				),
				'revoked'               => 0,
				'expires_at'            => $authCodeEntity->getExpiryDateTime()->format( 'Y-m-d H:i:s' ),
				'redirect_uri'          => $authCodeEntity->getRedirectUri(),
				'code_challenge'        => method_exists( $authCodeEntity, 'getCodeChallenge' ) ? $authCodeEntity->getCodeChallenge() : null,
				'code_challenge_method' => method_exists( $authCodeEntity, 'getCodeChallengeMethod' ) ? $authCodeEntity->getCodeChallengeMethod() : null,
				'created_at'            => current_time( 'mysql', true ),
			],
			[
				'%s',
				'%s',
				'%d',
				'%s',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			]
		);
	}

	public function revokeAuthCode( $codeId ): void {
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'mcp_oauth_auth_codes',
			[ 'revoked' => 1 ],
			[ 'id' => (string) $codeId ],
			[ '%d' ],
			[ '%s' ]
		);
	}

	public function isAuthCodeRevoked( $codeId ): bool {
		global $wpdb;

		$revoked = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT revoked FROM {$wpdb->prefix}mcp_oauth_auth_codes WHERE id = %s LIMIT 1",
				(string) $codeId
			)
		);

		return null === $revoked || 1 === (int) $revoked;
	}
}