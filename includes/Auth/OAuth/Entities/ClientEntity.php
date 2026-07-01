<?php

namespace WP_MCP_Server\Auth\OAuth\Entities;

use League\OAuth2\Server\Entities\ClientEntityInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ClientEntity implements ClientEntityInterface {

	private string $identifier;
	private string $name;
	private string $redirect_uri;
	private bool $confidential;

	public function __construct( string $identifier, string $name, string $redirect_uri, bool $confidential = true ) {
		$this->identifier   = $identifier;
		$this->name         = $name;
		$this->redirect_uri = $redirect_uri;
		$this->confidential = $confidential;
	}

	public function getIdentifier(): string {
		return $this->identifier;
	}

	public function getName(): string {
		return $this->name;
	}

	public function getRedirectUri(): string {
		return $this->redirect_uri;
	}

	public function isConfidential(): bool {
		return $this->confidential;
	}
}