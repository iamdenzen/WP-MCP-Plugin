<?php

namespace WP_MCP_Server\Auth\OAuth;

use DateInterval;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResourceServer;
use WP_MCP_Server\Auth\OAuth\Repositories\AccessTokenRepository;
use WP_MCP_Server\Auth\OAuth\Repositories\AuthCodeRepository;
use WP_MCP_Server\Auth\OAuth\Repositories\ClientRepository;
use WP_MCP_Server\Auth\OAuth\Repositories\RefreshTokenRepository;
use WP_MCP_Server\Auth\OAuth\Repositories\ScopeRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OAuthServerFactory {

	public static function authorization_server(): AuthorizationServer {
		CryptKeyManager::ensure_keys_exist();

		$client_repository        = new ClientRepository();
		$scope_repository         = new ScopeRepository();
		$access_token_repository  = new AccessTokenRepository();
		$auth_code_repository     = new AuthCodeRepository();
		$refresh_token_repository = new RefreshTokenRepository();

		$server = new AuthorizationServer(
			$client_repository,
			$access_token_repository,
			$scope_repository,
			CryptKeyManager::private_key_path(),
			CryptKeyManager::encryption_key()
		);

		$auth_code_grant = new AuthCodeGrant(
			$auth_code_repository,
			$refresh_token_repository,
			new DateInterval( 'PT10M' )
		);

		$auth_code_grant->setRefreshTokenTTL( new DateInterval( 'P30D' ) );

		$server->enableGrantType(
			$auth_code_grant,
			new DateInterval( 'PT1H' )
		);

		$refresh_grant = new RefreshTokenGrant( $refresh_token_repository );
		$refresh_grant->setRefreshTokenTTL( new DateInterval( 'P30D' ) );

		$server->enableGrantType(
			$refresh_grant,
			new DateInterval( 'PT1H' )
		);

		return $server;
	}

	public static function resource_server(): ResourceServer {
		CryptKeyManager::ensure_keys_exist();

		return new ResourceServer(
			new AccessTokenRepository(),
			CryptKeyManager::public_key_path()
		);
	}
}