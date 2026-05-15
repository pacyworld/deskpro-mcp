# Deskpro MCP Server - Agent Skill

This document provides structured instructions for AI agents to set up and configure the Deskpro MCP server.

## Overview

The Deskpro MCP server provides 22 tools (5 ticket + 5 action + 5 lookup + 2 instance + 5 meta) via stdio JSON-RPC transport. It supports two authentication methods: API Key (simple) and OAuth Bearer Token (no admin required), and can manage multiple Deskpro helpdesks simultaneously.

## Decision: Choose Authentication Method

Ask the user which method they prefer:

- **API Key** - if they have Deskpro admin access and want stable, long-lived credentials
- **OAuth Bearer Token** - if they don't have admin access and can extract tokens from their browser

If unsure, check whether the user has admin access. If yes, recommend **API Key** for stability. If no, use **OAuth Bearer Token**.

---

## Setup: API Key Method

### Prerequisites

1. PHP 8.2+ with `curl` and `json` extensions installed
2. The deskpro-mcp PHAR or repository cloned

### Steps

1. **Identify the site URL** - ask the user for their Deskpro URL.
   Format: `https://YOURCOMPANY.deskpro.com`

2. **Generate an API key** - the user (or a Deskpro admin) must:
   - Go to Admin > Apps & Integrations > API Keys
   - Click "New API Key"
   - Copy the generated key

3. **Create the config file**:
   ```bash
   mkdir -p ~/.config/deskpro-mcp
   ```

4. **Write the configuration** to `~/.config/deskpro-mcp/deskpro.json`:
   ```json
   {
       "auth_method": "apikey",
       "api_token": "<generated key>",
       "site_url": "https://<company>.deskpro.com"
   }
   ```

5. **Verify** - run the server:
   ```bash
   php bin/deskpro-mcp
   ```
   Check stderr for successful initialization.

---

## Setup: OAuth Bearer Token Method

### Prerequisites

1. PHP 8.2+ with `curl` and `json` extensions installed
2. The deskpro-mcp PHAR or repository cloned
3. An active Deskpro agent session in the browser

### Steps

1. **Extract tokens from the browser**:
   - Log into the Deskpro agent portal
   - Open browser developer tools (F12)
   - Go to Application > Cookies
   - Find and copy:
     - `app_access_token` (a JWT starting with `eyJ...`)
     - `app_refresh_token` (a long string starting with `def502...`)

2. **Identify the site URL** - the domain shown in the browser address bar.

3. **Create the config directory**:
   ```bash
   mkdir -p ~/.config/deskpro-mcp
   ```

4. **Write the configuration** to `~/.config/deskpro-mcp/deskpro.json`:
   ```json
   {
       "auth_method": "token",
       "site_url": "https://<company>.deskpro.com"
   }
   ```

5. **Write the token file** to `~/.config/deskpro-mcp/tokens.json`:
   ```json
   {
       "access_token": "<app_access_token cookie value>",
       "refresh_token": "<app_refresh_token cookie value>"
   }
   ```

6. **Close the Deskpro browser tab** - this is critical. See warning below.

7. **Verify** - run the server. Tokens auto-refresh every 3 hours.
   Refreshed tokens are written back to `tokens.json` automatically.

### Token File Hot-Reload

The `tokens.json` file is re-read before every API request. If tokens become
invalid, you (or an AI agent) can update `tokens.json` at any time and the
server will pick up the change on the next call - no restart needed.

### IMPORTANT: Browser Race Condition

Deskpro uses **single-use refresh tokens**. When a refresh token is used (by
the browser OR the MCP server), it is rotated and the old one is permanently
invalidated.

If the Deskpro web app is open in a browser simultaneously:
1. The browser periodically refreshes its session (every ~3 hours)
2. This rotates the refresh token, invalidating the one in `tokens.json`
3. The MCP server's next refresh attempt fails
4. All subsequent API calls return 401

**Prevention:** Always close the Deskpro browser tab after extracting tokens.

**Recovery:** If this happens, log into Deskpro fresh, extract new cookies,
update `tokens.json`. The server recovers on the next request without restart.

---

## Setup: Multi-Instance

For users managing multiple Deskpro helpdesks:

1. **Write a multi-instance config** to `~/.config/deskpro-mcp/deskpro.json`:
   ```json
   {
       "default": "primary",
       "instances": {
           "primary": {
               "auth_method": "apikey",
               "api_token": "<key>",
               "site_url": "https://support.example.com",
               "description": "Primary helpdesk"
           },
           "internal": {
               "auth_method": "token",
               "access_token": "eyJ...",
               "refresh_token": "def502...",
               "site_url": "https://internal.deskpro.com",
               "description": "Internal IT helpdesk"
           }
       }
   }
   ```

2. **Use the instance parameter** on any tool to target a specific helpdesk:
   ```
   deskpro_list_tickets({"instance": "internal"})
   ```

3. **Switch the default** at runtime:
   ```
   deskpro_switch_instance({"instance": "internal"})
   ```

---

## IDE Integration

### Windsurf

Add to the MCP configuration file:

```json
{
    "deskpro": {
        "command": "php",
        "args": ["/path/to/deskpro-mcp.phar"]
    }
}
```

Or with explicit config:

```json
{
    "deskpro": {
        "command": "php",
        "args": ["/path/to/deskpro-mcp.phar", "--config=/path/to/deskpro.json"]
    }
}
```

### From Source (no PHAR)

```json
{
    "deskpro": {
        "command": "php",
        "args": ["/path/to/deskpro-mcp/bin/deskpro-mcp"]
    }
}
```

---

## Verification

After setup, confirm the server works by calling these tools in order:

1. `deskpro_server_info` - should show version, auth method, and configured instances
2. `deskpro_list_departments` - should return a list of departments (confirms API connectivity)
3. `deskpro_list_tickets({"count": 3})` - should return 3 recent tickets

If any step fails, use `deskpro_error_guide` with the error code for troubleshooting.

---

## Common Workflows

### Search and triage tickets

```
1. deskpro_list_tickets({"status": "awaiting_agent", "count": 10})
2. deskpro_list_agents()
3. deskpro_assign_ticket({"ticket_id": <id>, "agent_id": <id>})
```

### Reply to a ticket

```
1. deskpro_get_ticket({"ticket_id": <id>})
2. deskpro_get_messages({"ticket_id": <id>})
3. deskpro_add_reply({"ticket_id": <id>, "message": "..."})
```

### Create a ticket

```
1. deskpro_list_departments()
2. deskpro_create_ticket({"subject": "...", "message": "...", "department_id": <id>})
```

---

## Troubleshooting

| Symptom | Likely cause | Resolution |
|---------|-------------|------------|
| 401 Unauthorized | Invalid or expired credentials | Check `api_token` or refresh tokens |
| 401 + "Token refresh failed" | Browser stole the refresh token | Close browser tab, extract fresh tokens into `tokens.json` |
| 403 Forbidden | Insufficient permissions | Verify agent permissions in Deskpro admin |
| Token refresh fails | Refresh token expired or rotated by browser | Extract fresh tokens, update `tokens.json` (no restart needed) |
| No config found | Config file not in search path | Set `DESKPRO_CONFIG` env var or use `--config=` |
| Connection timeout | Network or firewall issue | Verify site URL is reachable |
| Tokens not updating | `tokens.json` missing or wrong path | Verify file exists beside config, or set `token_file` explicitly |

Use `deskpro_error_guide` for detailed error resolution steps.
