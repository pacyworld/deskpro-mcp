# Setup Guide

## Requirements

- PHP 8.2 or later
- PHP extensions: `curl`, `json`, `phar` (for PHAR distribution)
- A Deskpro Cloud or On-Premise instance

## Installation

### Option A: PHAR (recommended)

Download the latest PHAR from [Releases](https://pacyworld.dev/pacyworld/deskpro-mcp/releases):

```bash
curl -LO https://pacyworld.dev/pacyworld/deskpro-mcp/releases/latest/download/deskpro-mcp.phar
chmod +x deskpro-mcp.phar
```

### Option B: From Source

```bash
git clone https://pacyworld.dev/pacyworld/deskpro-mcp.git
cd deskpro-mcp
```

To build the PHAR yourself:

```bash
php bin/build-phar.php
```

## Configuration

The server looks for `deskpro.json` in these locations (first match wins):

1. `DESKPRO_CONFIG` environment variable
2. `--config=/path/to/deskpro.json` CLI argument
3. `config/deskpro.json` (relative to server root)
4. `~/.config/deskpro-mcp/deskpro.json`
5. `/usr/local/etc/deskpro-mcp/deskpro.json`

Copy the sample config to get started:

```bash
mkdir -p ~/.config/deskpro-mcp
cp config/deskpro.json.sample ~/.config/deskpro-mcp/deskpro.json
```

### Authentication Methods

Two methods are supported. You only need one.

#### API Key (recommended)

Requires a Deskpro administrator to generate an API key under
Admin > Apps & Integrations > API Keys.

```json
{
    "default": "my-helpdesk",
    "instances": {
        "my-helpdesk": {
            "auth_method": "apikey",
            "api_token": "YOUR_API_KEY",
            "site_url": "https://yourcompany.deskpro.com",
            "description": "Primary helpdesk"
        }
    }
}
```

The `Authorization` header sent is: `key YOUR_API_KEY`.

#### OAuth Bearer Token

Uses JWT tokens from the Deskpro web app session. Does not require admin access
to set up, but tokens expire every 3 hours. The server automatically refreshes
them using the refresh token and writes the new tokens back to the config file.

```json
{
    "default": "my-helpdesk",
    "instances": {
        "my-helpdesk": {
            "auth_method": "token",
            "access_token": "eyJ...",
            "refresh_token": "def502...",
            "site_url": "https://yourcompany.deskpro.com",
            "description": "Primary helpdesk"
        }
    }
}
```

**To obtain tokens:** log into the Deskpro agent web app, then extract the
`app_access_token` and `app_refresh_token` cookies from your browser's
developer tools (Application > Cookies).

The `Authorization` header sent is: `Bearer <JWT>`.

### Multi-Instance Configuration

You can manage multiple Deskpro helpdesks from a single server. Each instance
has its own auth credentials and site URL. Tools accept an optional `instance`
parameter to target a specific helpdesk; otherwise the default is used.

```json
{
    "default": "production",
    "instances": {
        "production": {
            "auth_method": "apikey",
            "api_token": "PROD_KEY",
            "site_url": "https://support.example.com"
        },
        "staging": {
            "auth_method": "token",
            "access_token": "eyJ...",
            "refresh_token": "def502...",
            "site_url": "https://staging.deskpro.com"
        }
    }
}
```

Use `deskpro_list_instances` to see all configured instances and
`deskpro_switch_instance` to change the default at runtime.

### Legacy Single-Instance Format

A flat config (without the `instances` key) is also supported for simplicity.
The instance name is derived from the `site_url` hostname automatically.

```json
{
    "auth_method": "apikey",
    "api_token": "YOUR_API_KEY",
    "site_url": "https://yourcompany.deskpro.com"
}
```

## Running the Server

### Standalone (stdio)

```bash
# From source
php bin/deskpro-mcp

# PHAR
php deskpro-mcp.phar

# With explicit config
php bin/deskpro-mcp --config=/path/to/deskpro.json

# Via environment variable
DESKPRO_CONFIG=/path/to/deskpro.json php bin/deskpro-mcp
```

The server communicates over stdin/stdout using JSON-RPC 2.0 (MCP stdio
transport). Diagnostic messages go to stderr.

### IDE Integration

#### Windsurf

Add to your Windsurf MCP configuration:

```json
{
    "deskpro": {
        "command": "php",
        "args": ["/path/to/deskpro-mcp.phar"]
    }
}
```

With explicit config path:

```json
{
    "deskpro": {
        "command": "php",
        "args": ["/path/to/deskpro-mcp.phar", "--config=/path/to/deskpro.json"]
    }
}
```

From source (without PHAR):

```json
{
    "deskpro": {
        "command": "php",
        "args": ["/path/to/deskpro-mcp/bin/deskpro-mcp"]
    }
}
```

#### Other MCP Clients

Any MCP client that supports the stdio transport can use this server. Configure
it to spawn `php /path/to/deskpro-mcp.phar` (or `php bin/deskpro-mcp` from
source) and communicate over stdin/stdout.

## Troubleshooting

### Server does not start

- Verify PHP 8.2+ is installed: `php --version`
- Ensure required extensions: `php -m | grep -E 'curl|json|phar'`
- Check config file exists and is valid JSON: `php -r 'json_decode(file_get_contents("deskpro.json"));'`

### 401 Unauthorized errors

- **API Key**: verify `api_token` is correct and the key has not been revoked.
- **OAuth token**: both tokens may have expired. Get fresh tokens from the
  browser and update the config. See `deskpro_error_guide` for details.

### Token refresh fails

- The refresh endpoint is `POST /agent-api/authenticate/refresh` (not the
  standard OAuth `/oauth/token` endpoint, which returns 500).
- Both the JSON body (`refresh_token`) and a cookie (`app_refresh_token`)
  must contain the refresh token value.
- If the refresh token itself has expired, you need to log in again.

### Permission errors (403)

- The authenticated user may lack permissions for the operation.
- Some actions (e.g., generating API keys) require Deskpro admin privileges.
- Check department-level access for ticket operations.

Use the `deskpro_error_guide` tool for detailed troubleshooting of specific
error codes.
