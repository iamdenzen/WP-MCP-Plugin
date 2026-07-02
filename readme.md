# WP MCP Server

> Turn any WordPress website into a secure Model Context Protocol (MCP) server for AI assistants like ChatGPT, Claude, and other MCP-compatible clients.

WP MCP Server is a production-ready WordPress plugin that exposes WordPress functionality through the **Model Context Protocol (MCP)**, allowing AI assistants to securely interact with your website using standardized tools.

Whether you're building AI-powered workflows, connecting WordPress to LLMs, or exposing custom business data, WP MCP Server provides a secure, extensible, and WordPress-native solution.

---

## Features

- 🔒 OAuth 2.1 Authentication
- 🛡 Fine-grained permission scopes
- 🔧 Extensible tool registry
- 📄 WordPress Posts & Pages
- 🛒 WooCommerce support
- 🖼 Media Library support
- 🏷 Taxonomies & Categories
- 🔍 Search tools
- 📦 Custom Post Types
- 🧩 Advanced Custom Fields (ACF)
- 🗄 Custom database table support
- 📊 Logging & debugging
- ⚡ Performance-focused architecture
- 🧱 Built using modern object-oriented PHP
- 🔌 Compatible with any MCP client

---

## Why WP MCP Server?

Instead of building custom REST APIs or AI integrations for every project, WP MCP Server exposes your website through the standardized **Model Context Protocol**, allowing AI applications to discover and use your WordPress data automatically.

Examples include:

- ChatGPT Connectors
- Claude Connectors
- Claude Desktop
- Custom AI agents
- Internal company assistants
- Automation platforms
- AI-powered workflows

---

## Example Use Cases

- Search published posts
- Retrieve WordPress pages
- Read WooCommerce products
- Manage media
- Access ACF fields
- Query custom post types
- Connect custom business data
- Build AI assistants for clients
- Create internal knowledge bases

---

## Security

Security is a first-class concern.

WP MCP Server includes:

- OAuth 2.1 Authorization Code Flow
- PKCE support
- Fine-grained scopes
- Bearer token authentication
- Tool-level authorization
- WordPress capability checks
- Input validation
- Output sanitization
- Optional read-only mode

---

## Architecture

```
WordPress
    │
    ▼
WP MCP Server
    │
    ├── Authentication
    ├── Tool Registry
    ├── Tool Execution
    ├── Permissions
    ├── Logging
    └── MCP Transport
             │
             ▼
ChatGPT • Claude • Other MCP Clients
```

The plugin follows a modular architecture to make extending and maintaining it simple.

```
includes/

├── Admin/
├── Auth/
├── Core/
├── HTTP/
├── MCP/
├── Services/
├── Tools/
└── Utilities/
```

---

# Registering Custom MCP Tools

WP MCP Server allows plugins and themes to register their own MCP tools without modifying the core plugin.

## Register a Tool

Hook into the `wp_mcp_server_register_tools` action and register your tool with the provided `ToolRegistry` instance.

```php
use WP_MCP_Server\Tools\ToolRegistry;

add_action(
	'wp_mcp_server_register_tools',
	function ( ToolRegistry $registry ): void {
		$registry->register( new My_Custom_Tool() );
	}
);
```

## Create a Tool

Every tool must implement `WP_MCP_Server\Tools\Contracts\ToolInterface`.

Example:

```php
<?php

use WP_MCP_Server\Tools\Contracts\ToolInterface;

class My_Custom_Tool implements ToolInterface {

	public function name(): string {
		return 'my_custom_tool';
	}

	public function description(): string {
		return 'Returns custom data.';
	}

	public function input_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'id' => [
					'type'        => 'integer',
					'description' => 'Object ID.',
				],
			],
		];
	}

	public function output_schema(): ?array {
		return null;
	}

	public function annotations(): array {
		return [];
	}

	public function required_scopes(): array {
		return [ 'wp:read' ];
	}

	public function execute( array $arguments = [] ): array {
		return [
			'content' => [
				[
					'type' => 'text',
					'text' => 'Hello from my custom tool!',
				],
			],
		];
	}
}
```

## Best Practices

- Use a unique tool name (e.g. `myplugin_get_orders`).
- Validate and sanitize all input arguments.
- Return only the data required by the client.
- Define the minimum OAuth scopes required for the tool.
- Follow the MCP response format.
- Avoid exposing sensitive WordPress or plugin data.
- Keep tools focused on a single responsibility.

## Registration Flow

```
WordPress loads
        │
        ▼
WP MCP Server creates ToolRegistry
        │
        ▼
Built-in tools are registered
        │
        ▼
wp_mcp_server_register_tools action fires
        │
        ▼
Your plugin registers its tools
        │
        ▼
All tools become available through tools/list
```

---

## Requirements

- WordPress 6.8+
- PHP 8.1+
- HTTPS (recommended)
- Composer (for development)

---

## Installation

### Development

```bash
git clone https://github.com/yourusername/wp-mcp-server.git
cd wp-mcp-server

composer install
```

Activate the plugin from the WordPress admin dashboard.

---

## Roadmap

- [x] MCP Server
- [x] OAuth 2.1 Authentication
- [x] Tool Registry
- [x] WordPress Tools
- [x] WooCommerce Tools
- [x] ACF Support
- [x] Extensible Architecture
- [ ] Prompt Support
- [ ] Resource Support
- [ ] Admin Settings UI
- [ ] Tool Analytics
- [ ] Rate Limiting
- [ ] Caching
- [ ] Multisite Support

---

## Contributing

Contributions are welcome!

If you'd like to improve the project, fix bugs, or add new MCP tools, feel free to open an issue or submit a pull request.

---

## License

Licensed under the GNU General Public License v2.0 or later.

See the LICENSE file for details.

---

## About MCP

The **Model Context Protocol (MCP)** is an open standard for connecting AI assistants to external tools, services, and data sources through a consistent interface.

Learn more:

https://modelcontextprotocol.io

---

## Support

If you find this project useful, please consider giving it a ⭐ on GitHub.