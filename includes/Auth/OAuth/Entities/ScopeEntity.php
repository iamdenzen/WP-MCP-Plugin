<?php

namespace WP_MCP_Server\Auth\OAuth\Entities;

use League\OAuth2\Server\Entities\ScopeEntityInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ScopeEntity implements ScopeEntityInterface {

	private string $identifier;

	public function __construct( string $identifier ) {
		$this->identifier = $identifier;
	}

	public function getIdentifier(): string {
		return $this->identifier;
	}

	public function jsonSerialize(): string {
		return $this->identifier;
	}
}