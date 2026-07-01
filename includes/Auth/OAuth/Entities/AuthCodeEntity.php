<?php

namespace WP_MCP_Server\Auth\OAuth\Entities;

use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\Traits\AuthCodeTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AuthCodeEntity implements AuthCodeEntityInterface {
	use EntityTrait;
	use TokenEntityTrait;
	use AuthCodeTrait;
}