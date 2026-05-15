# Tool Reference

All tools are registered under the `deskpro_` prefix. Ticket, action, and
lookup tools accept an optional `instance` parameter to target a specific
Deskpro helpdesk; if omitted, the default instance is used.

## Ticket Tools

### deskpro_get_ticket

Get a Deskpro ticket by ID. Returns ticket details including subject, status,
agent, department, and messages.

| Parameter   | Type    | Required | Description                              |
|-------------|---------|----------|------------------------------------------|
| `ticket_id` | integer | yes      | Ticket ID                                |
| `instance`  | string  | no       | Instance name (uses default if omitted)  |

### deskpro_create_ticket

Create a new Deskpro ticket.

| Parameter       | Type    | Required | Description                              |
|-----------------|---------|----------|------------------------------------------|
| `subject`       | string  | yes      | Ticket subject                           |
| `message`       | string  | yes      | Initial message body (HTML or plain text)|
| `person_email`  | string  | no       | Email of the requester                   |
| `department_id` | integer | no       | Department ID                            |
| `priority`      | string  | no       | Priority value                           |
| `status`        | string  | no       | Status: awaiting_user, awaiting_agent, resolved, closed |
| `agent_id`      | integer | no       | Assign to agent ID                       |
| `instance`      | string  | no       | Instance name                            |

### deskpro_update_ticket

Update a Deskpro ticket. Only provided fields are updated.

| Parameter       | Type    | Required | Description                              |
|-----------------|---------|----------|------------------------------------------|
| `ticket_id`     | integer | yes      | Ticket ID to update                      |
| `subject`       | string  | no       | New subject                              |
| `status`        | string  | no       | New status                               |
| `priority`      | string  | no       | New priority                             |
| `department_id` | integer | no       | New department ID                        |
| `agent_id`      | integer | no       | New agent ID                             |
| `instance`      | string  | no       | Instance name                            |

### deskpro_search_tickets

Search Deskpro tickets by keyword, status, agent, department, or date range.

| Parameter       | Type    | Required | Description                                  |
|-----------------|---------|----------|----------------------------------------------|
| `query`         | string  | no       | Search keyword/phrase                        |
| `status`        | string  | no       | Filter by status                             |
| `agent_id`      | integer | no       | Filter by agent ID                           |
| `department_id` | integer | no       | Filter by department ID                      |
| `page`          | integer | no       | Page number (default 1)                      |
| `count`         | integer | no       | Results per page (default 25, max 200)       |
| `order_by`      | string  | no       | Sort field (e.g. date_created)               |
| `order_dir`     | string  | no       | Sort direction: asc or desc (default desc)   |
| `instance`      | string  | no       | Instance name                                |

### deskpro_list_tickets

List Deskpro tickets with pagination and optional filters.

| Parameter       | Type    | Required | Description                                  |
|-----------------|---------|----------|----------------------------------------------|
| `status`        | string  | no       | Filter by status                             |
| `agent_id`      | integer | no       | Filter by assigned agent ID                  |
| `department_id` | integer | no       | Filter by department ID                      |
| `page`          | integer | no       | Page number (default 1)                      |
| `count`         | integer | no       | Results per page (default 25, max 200)       |
| `order_by`      | string  | no       | Sort field                                   |
| `order_dir`     | string  | no       | Sort direction: asc or desc (default desc)   |
| `instance`      | string  | no       | Instance name                                |

## Action Tools

### deskpro_add_reply

Add a reply to a Deskpro ticket. The reply is visible to the user.

| Parameter   | Type    | Required | Description                              |
|-------------|---------|----------|------------------------------------------|
| `ticket_id` | integer | yes      | Ticket ID                                |
| `message`   | string  | yes      | Reply message body (HTML or plain text)  |
| `instance`  | string  | no       | Instance name                            |

### deskpro_add_note

Add an internal note to a Deskpro ticket. Notes are only visible to agents.

| Parameter   | Type    | Required | Description                              |
|-------------|---------|----------|------------------------------------------|
| `ticket_id` | integer | yes      | Ticket ID                                |
| `note`      | string  | yes      | Note content (HTML or plain text)        |
| `instance`  | string  | no       | Instance name                            |

### deskpro_get_messages

Get messages (replies and notes) for a Deskpro ticket.

| Parameter   | Type    | Required | Description                              |
|-------------|---------|----------|------------------------------------------|
| `ticket_id` | integer | yes      | Ticket ID                                |
| `page`      | integer | no       | Page number (default 1)                  |
| `count`     | integer | no       | Results per page (default 25)            |
| `instance`  | string  | no       | Instance name                            |

### deskpro_assign_ticket

Assign a Deskpro ticket to an agent.

| Parameter   | Type    | Required | Description                              |
|-------------|---------|----------|------------------------------------------|
| `ticket_id` | integer | yes      | Ticket ID                                |
| `agent_id`  | integer | yes      | Agent ID to assign the ticket to         |
| `instance`  | string  | no       | Instance name                            |

### deskpro_change_status

Change the status of a Deskpro ticket.

| Parameter   | Type    | Required | Description                                              |
|-------------|---------|----------|----------------------------------------------------------|
| `ticket_id` | integer | yes      | Ticket ID                                                |
| `status`    | string  | yes      | New status: awaiting_user, awaiting_agent, resolved, closed |
| `instance`  | string  | no       | Instance name                                            |

## Lookup Tools

### deskpro_list_departments

List available Deskpro departments.

| Parameter  | Type   | Required | Description     |
|------------|--------|----------|-----------------|
| `instance` | string | no       | Instance name   |

### deskpro_list_agents

List Deskpro agents (support staff).

| Parameter  | Type   | Required | Description     |
|------------|--------|----------|-----------------|
| `instance` | string | no       | Instance name   |

### deskpro_list_statuses

List available Deskpro ticket statuses.

| Parameter  | Type   | Required | Description     |
|------------|--------|----------|-----------------|
| `instance` | string | no       | Instance name   |

### deskpro_get_ticket_fields

Get Deskpro ticket custom field definitions.

| Parameter  | Type   | Required | Description     |
|------------|--------|----------|-----------------|
| `instance` | string | no       | Instance name   |

### deskpro_list_filters

List saved Deskpro ticket filters (views).

| Parameter  | Type   | Required | Description     |
|------------|--------|----------|-----------------|
| `instance` | string | no       | Instance name   |

## Instance Tools

### deskpro_list_instances

List all configured Deskpro instances. Shows which instance is the current
default. Takes no parameters.

### deskpro_switch_instance

Switch the active default Deskpro instance. All subsequent tool calls without
an explicit instance parameter will use this instance.

| Parameter  | Type   | Required | Description                                        |
|------------|--------|----------|----------------------------------------------------|
| `instance` | string | yes      | Name of the instance to set as default             |

## Meta Tools

### deskpro_help

Get context-aware help on how to use Deskpro MCP tools.

| Parameter | Type   | Required | Description                                                 |
|-----------|--------|----------|-------------------------------------------------------------|
| `topic`   | string | no       | Topic: getting_started, authentication, multi_instance, best_practices |

### deskpro_tool_categories

Get a list of tool categories and their descriptions.

| Parameter  | Type   | Required | Description                                        |
|------------|--------|----------|----------------------------------------------------|
| `category` | string | no       | Category: tickets, actions, lookups, instances, meta |

### deskpro_usage_examples

Get common workflow examples for Deskpro operations.

| Parameter  | Type   | Required | Description                                                |
|------------|--------|----------|------------------------------------------------------------|
| `workflow` | string | no       | Workflow: search_tickets, create_ticket, reply_to_ticket, triage_tickets |

### deskpro_server_info

Get information about this Deskpro MCP server including version, capabilities,
and configured instances. Takes no parameters.

### deskpro_error_guide

Get information about common error codes and how to resolve them.

| Parameter    | Type   | Required | Description                                                            |
|--------------|--------|----------|------------------------------------------------------------------------|
| `error_code` | string | no       | Error code: UNAUTHORIZED, FORBIDDEN, NOT_FOUND, RATE_LIMITED, TOKEN_EXPIRED, INVALID_CONFIG |
