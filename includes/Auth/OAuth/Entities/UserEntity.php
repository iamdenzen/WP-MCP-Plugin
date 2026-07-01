<?php

namespace WP_MCP_Server\Auth\OAuth\Entities;

use League\OAuth2\Server\Entities\UserEntityInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UserEntity implements UserEntityInterface {

	private string $identifier;

	public function __construct( int $user_id ) {
		$this->identifier = (string) $user_id;
	}

	public function getIdentifier(): string {
		return $this->identifier;
	}
}