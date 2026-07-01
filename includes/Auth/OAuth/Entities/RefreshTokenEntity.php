<?php

namespace WP_MCP_Server\Auth\OAuth\Entities;

use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\RefreshTokenTrait;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RefreshTokenEntity implements RefreshTokenEntityInterface {
	use EntityTrait;
	use RefreshTokenTrait;
}