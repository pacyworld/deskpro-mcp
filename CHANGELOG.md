# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[1.0.0]: https://pacyworld.dev/pacyworld/deskpro-mcp/releases/tag/v1.0.0
