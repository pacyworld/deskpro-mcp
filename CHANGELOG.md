# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] - 2026-10-22

### Changed
- Re-vendored the Enchilada libraries onto the EnchiladaCurl split: new `libraries/EnchiladaCurl/` (abstract curl base) and `libraries/EnchiladaWorkerHTTP/` (curl executed in worker processes), with `EnchiladaMultiHTTP` alongside on the same base; `EnchiladaHTTP` stays the standalone blocking client. `EnchiladaMCP` refreshed (adds `ElicitationRequired`, `McpComplete`, `McpPrompt`, `mcp.inc.php`) and `Tortilla` refreshed (adds `OAuthCallbackHelper`)
- CI FreeBSD job installs php84-curl and php84-tokenizer

### Added
- curl behaviour suite and one-door-to-curl lint vendored into `tests/enchilada-curl/`, run in CI against `libraries/` on the FreeBSD job and a new Windows job

## [1.1.5] - 2026-09-13

### Fixed
- Stdio no longer stalls on hosts whose stdin never reports EOF or is a regular file (re-vendored Tortilla `StdioTransport`: bounded no-EOF read retries, regular-file stdin degrades to blocking, in-flight requests are answered before an EOF stop)
- A response body that is not valid JSON completes the request with an error instead of leaving the wait loop spinning (re-vendored `EnchiladaMultiHTTP`)
- Diagnostics no longer write unbounded output to stderr: they go through `EnchiladaMCP\Logger`, silent by default, with a `DESKPRO_MCP_LOG` file and a budgeted stderr mirror; `--log`, `--log-level` and `--io-mode` flags

## [1.1.4] - 2026-09-10

### Changed
- Vendored HTTP library layout moved from `libraries/HTTP/` to eponymous directories (`libraries/EnchiladaHTTP/`, `libraries/EnchiladaMultiHTTP/`), which the framework autoloader resolves natively for legacy global classes — the guarded `require_once` in `DeskproClient` is gone
- Enchilada transport split: `EnchiladaMCP` re-vendored as the slim protocol core (gains `Logger`, `InstanceRegistry`, `ToolWarningInterface`); the stdio transport moves to `Enchilada\Tortilla` with the composition root wired in `bin/deskpro-mcp`. No Comal vendored, so the transport runs deterministic blocking I/O with a startup NOTE.
- `DeskproClient` now drives `EnchiladaMultiHTTP` through `Tortilla\HttpClient` — both the main API surfaces and the token-refresh POST — so progress notifications flow during long Deskpro API waits on blocking stdio; `InstanceManager::setHttpTransport()` wires the loop/progress from the composition root. Transport failures map to the historical `false` contract.
- HTTP libraries moved to `libraries/HTTP/` and `EnchiladaMultiHTTP` added.

## [1.1.3] - 2026-07-23

### Fixed
- Added the missing vendored `EnchiladaMCP/ToolResult.class.php`, which `McpServer` requires

## [1.1.2] - 2026-07-23

### Changed
- Re-vendored `EnchiladaMCP` with graceful lifecycle handling: an `onReinitialize` hook, and detection of stdout write failures

## [1.1.1] - 2026-06-30

### Added
- MCP tool annotations (`readOnlyHint`) on read-only tools: ticket get/search/list, instance list, all meta tools, get_messages, and all lookup tools. Vendored from updated EnchiladaMCP library. Lets MCP clients distinguish safe reads from tools that mutate Deskpro tickets.

## [1.1.0] - 2026-05-15

### Added

- External token file (`tokens.json`) with hot-reload on every request
- Default token file convention: `tokens.json` beside the config file
- `token_file` config option to specify a custom token file path
- Clear error messaging about browser token race condition on refresh failure

### Fixed

- Token refresh now returns success/failure; `ensureToken()` throws on failure
  instead of silently sending a dead token (fixes #2)
- Refresh request body now matches Deskpro protocol (`access_token` + `refresh_token: "COOKIE"`)
- Refresh token is no longer overwritten with the literal string "COOKIE" from response
- Fixed `ticket_fields` endpoint (correct path: `ticket_custom_fields`)
- Fixed `create_ticket`, `update_ticket`, `assign_ticket` API field names
  (`department_id` -> `department`, `agent_id` -> `agent`)
- Fixed PUT 204 No Content handling (no longer treated as failure)
- Fixed uninitialized typed property error on token file reload
- Synced EnchiladaHTTP from upstream (adds `getHttpCode()` support)

### Changed

- Token persistence prefers external token file over inline config
- OAuth token config no longer requires inline `access_token`/`refresh_token`
  (reads from `tokens.json` by default)

## [1.0.0] - 2026-05-15

### Added

- Initial release with 22 MCP tools for Deskpro helpdesk management
- **Ticket tools** (5): get, create, update, search, list with pagination and filters
- **Action tools** (5): reply, internal note, get messages, assign to agent, change status
- **Lookup tools** (5): departments, agents, statuses, custom fields, saved filters
- **Instance tools** (2): list configured instances, switch active default
- **Meta tools** (5): help, tool categories, usage examples, server info, error guide
- Dual authentication: API Key and OAuth Bearer token with automatic refresh
- Multi-instance support for managing multiple Deskpro helpdesks
- Legacy single-instance config format (auto-detects instance name from URL)
- Token refresh persists new tokens back to the config file
- PHAR build script for single-file distribution (~97 KB)
- Forgejo CI workflow (lint, PHAR build, smoke test)
- Forgejo release workflow (version stamp, PHAR upload on tagged release)
- Comprehensive documentation: README, SETUP.md, TOOLS.md, SKILL.md

[1.1.5]: https://pacyworld.dev/pacyworld/deskpro-mcp/releases/tag/v1.1.5
[1.1.4]: https://pacyworld.dev/pacyworld/deskpro-mcp/releases/tag/v1.1.4
[1.1.3]: https://pacyworld.dev/pacyworld/deskpro-mcp/releases/tag/v1.1.3
[1.1.2]: https://pacyworld.dev/pacyworld/deskpro-mcp/releases/tag/v1.1.2
[1.1.1]: https://pacyworld.dev/pacyworld/deskpro-mcp/releases/tag/v1.1.1
[1.1.0]: https://pacyworld.dev/pacyworld/deskpro-mcp/releases/tag/v1.1.0
[1.0.0]: https://pacyworld.dev/pacyworld/deskpro-mcp/releases/tag/v1.0.0
