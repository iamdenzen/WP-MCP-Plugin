<?php

namespace WP_MCP_Server\Auth\OAuth\Entities;

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AccessTokenEntity implements AccessTokenEntityInterface {
	use EntityTrait;
	use TokenEntityTrait;
	use AccessTokenTrait;
}