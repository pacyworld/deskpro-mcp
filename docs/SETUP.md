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
to set up, but tokens expire every 3 hours. The server refreshes them on-demand
(when a tool call is made and the token is near expiry) and persists new tokens
for future use.

Tokens are stored in a separate **token file** (`tokens.json`), located in the
same directory as the config file by default. This file is hot-reloaded on every
API request, so you can update tokens externally without restarting the server.

**Minimal config** (tokens.json is auto-detected beside the config file):

```json
{
    "auth_method": "token",
    "site_url": "https://yourcompany.deskpro.com"
}
```

**Token file** (`tokens.json` in the same directory):

```json
{
    "access_token": "eyJ...",
    "refresh_token": "def502..."
}
```

**Custom token file path** (optional):

```json
{
    "auth_method": "token",
    "token_file": "/path/to/tokens.json",
    "site_url": "https://yourcompany.deskpro.com"
}
```

**To obtain tokens:** log into the Deskpro agent web app, then extract the
`app_access_token` and `app_refresh_token` cookies from your browser's
developer tools (Application > Cookies). Save them into `tokens.json`.

The `Authorization` header sent is: `Bearer <JWT>`.

> **WARNING: Browser Token Race Condition**
>
> Deskpro rotates refresh tokens on use - each refresh token can only be used
> once. If the Deskpro web app is open in a browser at the same time as the
> MCP server, the browser will periodically refresh its session and **invalidate
> the refresh token** that the MCP server is holding.
>
> When this happens, the MCP server's next refresh attempt will fail and API
> calls will return 401 errors.
>
> **Recommendations:**
> - Close the Deskpro browser tab after extracting tokens
> - If you must keep the browser open, re-extract tokens after any browser
>   refresh and update `tokens.json` (no server restart needed)
> - For long-lived, race-free access, use the **API Key** method instead

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
- **OAuth token**: the access token (JWT) has expired and auto-refresh failed.
  Common causes:
  - The refresh token was invalidated by a browser session (see below).
  - The refresh token itself expired (unclear TTL, possibly days/weeks).
  - Fix: extract fresh tokens from the browser and update `tokens.json`.
    No server restart is needed - the token file is hot-reloaded.

### Token refresh fails / Browser race condition

- **Most common cause:** A Deskpro browser tab refreshed its session, rotating
  the refresh token. Deskpro tokens are single-use - once the browser uses the
  refresh token, the copy held by the MCP server is permanently dead.
- **Fix:** Close the browser tab, log in fresh, extract new cookies into
  `tokens.json`. The server picks up changes on the next request.
- **Prevention:** Close the Deskpro browser tab after extracting tokens.
  Alternatively, use the API Key method which has no expiry or rotation.
- The refresh endpoint is `POST /agent-api/authenticate/refresh` (not the
  standard OAuth `/oauth/token` endpoint, which returns 500).
- The request body must include `access_token`, `refresh_token: "COOKIE"`,
  and `isSession: false`. The actual refresh token goes in the
  `Cookie: app_refresh_token=...` header.
- If refresh fails, the server throws a RuntimeException with a clear
  message explaining the race condition and how to fix it.

### Permission errors (403)

- The authenticated user may lack permissions for the operation.
- Some actions (e.g., generating API keys) require Deskpro admin privileges.
- Check department-level access for ticket operations.

Use the `deskpro_error_guide` tool for detailed troubleshooting of specific
error codes.
