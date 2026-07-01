<?php

namespace WP_MCP_Server\MCP;

use WP_MCP_Server\Tools\BuiltInToolProvider;
use WP_MCP_Server\Tools\ToolRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Server {

	private Dispatcher $dispatcher;
	private array $auth_context;

	public function __construct( array $auth_context = [] ) {
		$this->auth_context = $auth_context;

		$registry = new ToolRegistry();

		$provider = new BuiltInToolProvider();
		$provider->register( $registry );

		$registry->register_defaults();

		$this->dispatcher = new Dispatcher( $registry, $auth_context );
	}

	public function handle( array $payload ): array {
		$request = Request::from_array( $payload );

		if ( ! $request->is_valid() ) {
			return Response::error(
				$request->get_id(),
				-32600,
				'Invalid Request'
			);
		}

		return $this->dispatcher->dispatch( $request );
	}
}