<?php

namespace WP_MCP_Server\Auth\OAuth;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CryptKeyManager {

	public static function private_key_path(): string {
		return self::key_dir() . '/private.key';
	}

	public static function public_key_path(): string {
		return self::key_dir() . '/public.key';
	}

	public static function encryption_key(): string {
		$key = (string) get_option( 'wp_mcp_oauth_encryption_key', '' );

		if ( '' === $key ) {
			$key = base64_encode( random_bytes( 32 ) );
			update_option( 'wp_mcp_oauth_encryption_key', $key, false );
		}

		return base64_decode( $key );
	}

	public static function ensure_keys_exist(): void {
		$dir = self::key_dir();

		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$private_key = self::private_key_path();
		$public_key  = self::public_key_path();

		if ( file_exists( $private_key ) && file_exists( $public_key ) ) {
			return;
		}

		$config = [
			'private_key_bits' => 2048,
			'private_key_type' => OPENSSL_KEYTYPE_RSA,
		];

		$key = openssl_pkey_new( $config );

		if ( false === $key ) {
			throw new \RuntimeException( 'Could not generate OAuth RSA keys.' );
		}

		openssl_pkey_export( $key, $private_key_pem );

		$details        = openssl_pkey_get_details( $key );
		$public_key_pem = $details['key'] ?? '';

		file_put_contents( $private_key, $private_key_pem );
		file_put_contents( $public_key, $public_key_pem );

		@chmod( $private_key, 0600 );
		@chmod( $public_key, 0600 );
	}

	private static function key_dir(): string {
		$upload_dir = wp_upload_dir();

		return trailingslashit( $upload_dir['basedir'] ) . 'wp-mcp-oauth-keys';
	}
}