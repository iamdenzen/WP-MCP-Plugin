<?php

namespace WP_MCP_Server\Tools\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface ToolInterface {

	public function name(): string;

	public function description(): string;

	public function input_schema(): array;

	public function output_schema(): ?array;

	public function annotations(): array;

	public function required_scopes(): array;

	public function execute( array $arguments = [] ): array;
}