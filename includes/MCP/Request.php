<?php

namespace WP_MCP_Server\MCP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Request {

	private mixed $id;
	private string $jsonrpc;
	private string $method;
	private array $params;

	private function __construct( mixed $id, string $jsonrpc, string $method, array $params = [] ) {
		$this->id      = $id;
		$this->jsonrpc = $jsonrpc;
		$this->method  = $method;
		$this->params  = $params;
	}

	public static function from_array( array $payload ): self {
		return new self(
			$payload['id'] ?? null,
			(string) ( $payload['jsonrpc'] ?? '' ),
			(string) ( $payload['method'] ?? '' ),
			is_array( $payload['params'] ?? null ) ? $payload['params'] : []
		);
	}

	public function is_valid(): bool {
		if ( '2.0' !== $this->jsonrpc ) {
			return false;
		}

		if ( '' === trim( $this->method ) ) {
			return false;
		}

		return true;
	}

	public function get_id(): mixed {
		return $this->id;
	}

	public function get_method(): string {
		return $this->method;
	}

	public function get_params(): array {
		return $this->params;
	}
}