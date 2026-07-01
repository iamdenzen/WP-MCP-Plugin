<?php

namespace WP_MCP_Server\Auth\OAuth;

use WP_MCP_Server\Auth\OAuth\CryptKeyManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OAuthInstaller {

	public static function install(): void {
		
		add_option( 'wp_mcp_server_logging_enabled', false, '', false );
		add_option( 'wp_mcp_server_logging_level', 'info', '', false );
		
		CryptKeyManager::ensure_keys_exist();
		
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$clients_table        = $wpdb->prefix . 'mcp_oauth_clients';
		$auth_codes_table     = $wpdb->prefix . 'mcp_oauth_auth_codes';
		$access_tokens_table  = $wpdb->prefix . 'mcp_oauth_access_tokens';
		$refresh_tokens_table = $wpdb->prefix . 'mcp_oauth_refresh_tokens';
		$grants_table		  = $wpdb->prefix . 'mcp_oauth_grants';

		dbDelta(
			"CREATE TABLE {$clients_table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				client_id VARCHAR(100) NOT NULL,
				client_secret_hash VARCHAR(255) NOT NULL,
				name VARCHAR(190) NOT NULL,
				redirect_uris LONGTEXT NOT NULL,
				scopes LONGTEXT NOT NULL,
				active TINYINT(1) NOT NULL DEFAULT 1,
				created_by BIGINT UNSIGNED NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY client_id (client_id),
				KEY active (active)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$auth_codes_table} (
				id VARCHAR(100) NOT NULL,
				client_id VARCHAR(100) NOT NULL,
				user_id BIGINT UNSIGNED NOT NULL,
				scopes LONGTEXT NOT NULL,
				revoked TINYINT(1) NOT NULL DEFAULT 0,
				expires_at DATETIME NOT NULL,
				redirect_uri TEXT NULL,
				code_challenge VARCHAR(255) NULL,
				code_challenge_method VARCHAR(20) NULL,
				created_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY client_id (client_id),
				KEY user_id (user_id),
				KEY expires_at (expires_at)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$access_tokens_table} (
				id VARCHAR(100) NOT NULL,
				grant_id BIGINT UNSIGNED NULL,
				client_id VARCHAR(100) NOT NULL,
				user_id BIGINT UNSIGNED NULL,
				scopes LONGTEXT NOT NULL,
				revoked TINYINT(1) NOT NULL DEFAULT 0,
				expires_at DATETIME NOT NULL,
				created_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY grant_id (grant_id),
				KEY client_id (client_id),
				KEY user_id (user_id),
				KEY expires_at (expires_at),
				KEY revoked (revoked)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$refresh_tokens_table} (
				id VARCHAR(100) NOT NULL,
				grant_id BIGINT UNSIGNED NULL,
				access_token_id VARCHAR(100) NOT NULL,
				revoked TINYINT(1) NOT NULL DEFAULT 0,
				expires_at DATETIME NOT NULL,
				created_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY grant_id (grant_id),
				KEY access_token_id (access_token_id),
				KEY expires_at (expires_at),
				KEY revoked (revoked)
			) {$charset_collate};"
		);
		
		dbDelta(
			"CREATE TABLE {$grants_table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				client_id VARCHAR(100) NOT NULL,
				user_id BIGINT UNSIGNED NOT NULL,
				scopes LONGTEXT NOT NULL,
				status VARCHAR(20) NOT NULL DEFAULT 'pending',
				last_used_at DATETIME NULL,
				revoked_at DATETIME NULL,
				revoked_by BIGINT UNSIGNED NULL,
				user_agent TEXT NULL,
				ip_address VARCHAR(100) NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				KEY client_id (client_id),
				KEY user_id (user_id),
				KEY revoked_at (revoked_at),
				KEY last_used_at (last_used_at)
			) {$charset_collate};"
		);

		update_option( 'wp_mcp_server_oauth_db_version', '1.1.0', false );
	}
}