<?php

namespace WP_MCP_Server\Auth\OAuth\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GrantRepository {

	public function create_or_update(
		string $client_id,
		int $user_id,
		array $scopes
	): int {
		global $wpdb;

		$table = $wpdb->prefix . 'mcp_oauth_grants';

		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table}
				WHERE client_id = %s
				AND user_id = %d
				AND revoked_at IS NULL
				LIMIT 1",
				$client_id,
				$user_id
			)
		);

		$data = [
			'scopes'     => wp_json_encode( array_values( array_unique( $scopes ) ) ),
			'updated_at' => current_time( 'mysql', true ),
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] )
				? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
				: null,
			'ip_address' => $this->ip_address(),
		];

		if ( $existing_id ) {
			$wpdb->update(
				$table,
				$data,
				[ 'id' => (int) $existing_id ],
				[ '%s', '%s', '%s', '%s' ],
				[ '%d' ]
			);

			return (int) $existing_id;
		}

		$wpdb->insert(
			$table,
			array_merge(
				$data,
				[
					'client_id'  => $client_id,
					'user_id'    => $user_id,
					'created_at' => current_time( 'mysql', true ),
				]
			),
			[
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
				'%s',
			]
		);

		return (int) $wpdb->insert_id;
	}
	
	public function revoke( int $grant_id, int $revoked_by ): bool {
		global $wpdb;

		if ( $grant_id <= 0 ) {
			return false;
		}

		$now = current_time( 'mysql', true );

		$wpdb->update(
			$wpdb->prefix . 'mcp_oauth_grants',
			[
				'revoked_at' => $now,
				'revoked_by' => $revoked_by,
				'updated_at' => $now,
			],
			[ 'id' => $grant_id ],
			[ '%s', '%d', '%s' ],
			[ '%d' ]
		);

		$wpdb->update(
			$wpdb->prefix . 'mcp_oauth_access_tokens',
			[ 'revoked' => 1 ],
			[ 'grant_id' => $grant_id ],
			[ '%d' ],
			[ '%d' ]
		);

		$wpdb->update(
			$wpdb->prefix . 'mcp_oauth_refresh_tokens',
			[ 'revoked' => 1 ],
			[ 'grant_id' => $grant_id ],
			[ '%d' ],
			[ '%d' ]
		);

		return true;
	}

	public function touch_last_used( int $grant_id ): void {
		global $wpdb;

		if ( $grant_id <= 0 ) {
			return;
		}

		$wpdb->update(
			$wpdb->prefix . 'mcp_oauth_grants',
			[
				'last_used_at' => current_time( 'mysql', true ),
				'updated_at'   => current_time( 'mysql', true ),
			],
			[ 'id' => $grant_id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);
	}

	private function ip_address(): ?string {
		$ip = $_SERVER['REMOTE_ADDR'] ?? '';

		if ( '' === $ip ) {
			return null;
		}

		return sanitize_text_field( wp_unslash( $ip ) );
	}
}