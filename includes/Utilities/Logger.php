<?php

namespace WP_MCP_Server\Utilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Logger {

	private const MAX_FILE_SIZE = 10485760; // 10 MB.

	private const CHANNELS = [
		'oauth',
		'mcp',
		'security',
		'admin',
		'plugin',
	];

	public static function oauth( string $message, array $context = [], string $level = 'info' ): void {
		self::write( 'oauth', $level, $message, $context );
	}

	public static function mcp( string $message, array $context = [], string $level = 'info' ): void {
		self::write( 'mcp', $level, $message, $context );
	}

	public static function security( string $message, array $context = [], string $level = 'warning' ): void {
		self::write( 'security', $level, $message, $context );
	}

	public static function admin( string $message, array $context = [], string $level = 'info' ): void {
		self::write( 'admin', $level, $message, $context );
	}

	public static function plugin( string $message, array $context = [], string $level = 'info' ): void {
		self::write( 'plugin', $level, $message, $context );
	}

	private static function write( string $channel, string $level, string $message, array $context = [] ): void {
		if ( ! self::enabled() ) {
			return;
		}

		if ( ! in_array( $channel, self::CHANNELS, true ) ) {
			$channel = 'plugin';
		}

		$level = strtolower( $level );

		if ( ! self::should_log_level( $level ) ) {
			return;
		}

		$context = self::sanitize_context( $context );

		$line = sprintf(
			"[%s] [%s] %s %s\n",
			gmdate( 'Y-m-d H:i:s' ),
			strtoupper( $level ),
			$message,
			empty( $context ) ? '' : wp_json_encode( $context )
		);

		$dir = self::log_dir();

		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
			self::protect_dir( $dir );
		}

		$file = trailingslashit( $dir ) . $channel . '.log';

		self::rotate_if_needed( $file );

		file_put_contents( $file, $line, FILE_APPEND | LOCK_EX );
	}

	private static function enabled(): bool {
		return (bool) get_option( 'wp_mcp_server_logging_enabled', false );
	}

	private static function min_level(): string {
		return (string) get_option( 'wp_mcp_server_logging_level', 'info' );
	}

	private static function should_log_level( string $level ): bool {
		$weights = [
			'debug'   => 10,
			'info'    => 20,
			'warning' => 30,
			'error'   => 40,
		];

		$current = self::min_level();

		return ( $weights[ $level ] ?? 20 ) >= ( $weights[ $current ] ?? 20 );
	}

	private static function sanitize_context( array $context ): array {
		$blocked_keys = [
			'client_secret',
			'client_secret_hash',
			'access_token',
			'refresh_token',
			'authorization',
			'code',
			'auth_code',
			'code_verifier',
			'code_challenge',
			'password',
			'token',
		];

		foreach ( $context as $key => $value ) {
			$key_lower = strtolower( (string) $key );

			if ( in_array( $key_lower, $blocked_keys, true ) ) {
				$context[ $key ] = '[redacted]';
				continue;
			}

			if ( is_array( $value ) ) {
				$context[ $key ] = self::sanitize_context( $value );
			}
		}

		return $context;
	}

	private static function log_dir(): string {
		$upload_dir = wp_upload_dir();

		return trailingslashit( $upload_dir['basedir'] ) . 'wp-mcp-server/logs';
	}

	private static function protect_dir( string $dir ): void {
		file_put_contents(
			trailingslashit( $dir ) . 'index.php',
			"<?php\n// Silence is golden.\n"
		);

		file_put_contents(
			trailingslashit( $dir ) . '.htaccess',
			"Deny from all\n"
		);
	}

	private static function rotate_if_needed( string $file ): void {
		if ( ! file_exists( $file ) ) {
			return;
		}

		if ( filesize( $file ) < self::MAX_FILE_SIZE ) {
			return;
		}

		$rotated = sprintf(
			'%s-%s.log',
			preg_replace( '/\.log$/', '', $file ),
			gmdate( 'Ymd-His' )
		);

		rename( $file, $rotated );
	}
}