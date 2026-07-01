<?php

namespace WP_MCP_Server\MCP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Response {

	public static function result( mixed $id, array $result ): array {
		return [
			'jsonrpc' => '2.0',
			'id'      => $id,
			'result'  => $result,
		];
	}

	public static function error( mixed $id, int $code, string $message, array $data = [] ): array {
		$error = [
			'code'    => $code,
			'message' => $message,
		];

		if ( ! empty( $data ) ) {
			$error['data'] = $data;
		}

		return [
			'jsonrpc' => '2.0',
			'id'      => $id,
			'error'   => $error,
		];
	}
}