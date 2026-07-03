<?php
namespace WP_MCP_Server\Auth\OAuth;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ScopeRegistry {

	public static function all(): array {
		$scopes = [
			'wp:read'            => 'Read general WordPress data.',
			//'wp:write'           => 'Write general WordPress data.',

			'posts:read'         => 'Read posts.',
			//'posts:write'        => 'Create or update posts.',

			'pages:read'         => 'Read pages.',
			//'pages:write'        => 'Create or update pages.',

			'media:read'         => 'Read media library items.',
			//'media:write'        => 'Upload or update media.',

			'woocommerce:read'   => 'Read WooCommerce data.',
			//'woocommerce:write'  => 'Create or update WooCommerce data.',

			//'acf:read'           => 'Read ACF fields.',
			//'acf:write'          => 'Write ACF fields.',
		];

		return apply_filters( 'wp_mcp_server_oauth_scopes', $scopes );
	}

	public static function exists( string $scope ): bool {
		return array_key_exists( $scope, self::all() );
	}

	public static function filter_valid( array $scopes ): array {
		return array_values(
			array_filter(
				array_unique( $scopes ),
				[ self::class, 'exists' ]
			)
		);
	}
}