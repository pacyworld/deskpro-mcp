<?php
/**
 * Deskpro MCP Server - Meta Tools
 *
 * Discovery and guidance tools that help AI agents understand how
 * to use the Deskpro MCP server effectively.
 *
 * @package    DeskproMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use DeskproMCP\InstanceManager;

/**
 * MetaTools - Self-documenting tools for the Deskpro MCP server.
 */
class MetaTools
{
    /** @var string */
    private string $serverVersion;

    /** @var InstanceManager */
    private InstanceManager $manager;

    /**
     * @param InstanceManager $manager       Instance manager
     * @param string          $serverVersion Server version string
     */
    public function __construct(InstanceManager $manager, string $serverVersion = '1.0.0')
    {
        $this->manager = $manager;
        $this->serverVersion = $serverVersion;
    }

    /**
     * Get context-aware help.
     */
    #[McpTool(
        name: 'deskpro_help',
        description: 'Get context-aware help on how to use Deskpro MCP tools.',
        inputSchema: [
            'type' => 'object',
            'properties' => [
                'topic' => [
                    'type' => 'string',
                    'description' => 'Topic: getting_started, authentication, multi_instance, best_practices',
                ],
            ],
        ]
    )]
    public function deskpro_help(?string $topic = null): string
    {
        $topics = $this->helpTopics();

        if ($topic !== null && isset($topics[$topic])) {
            return $topics[$topic];
        }

        $lines = [
            "Deskpro MCP Server Help",
            "=======================",
            "",
            "This server provides Deskpro helpdesk management tools via the",
            "Model Context Protocol. It supports API Key and OAuth Bearer",
            "token authentication.",
            "",
            "Available help topics:",
        ];
        foreach ($topics as $key => $content) {
            $summary = strtok($content, "\n");
            $lines[] = "  - {$key}: {$summary}";
        }
        $lines[] = "";
        $lines[] = "Call deskpro_help with a topic parameter for detailed guidance.";

        return implode("\n", $lines);
    }

    /**
     * Get tool categories.
     */
    #[McpTool(
        name: 'deskpro_tool_categories',
        description: 'Get a list of tool categories and their descriptions.',
        inputSchema: [
            'type' => 'object',
            'properties' => [
                'category' => [
                    'type' => 'string',
                    'description' => 'Specific category: tickets, actions, lookups, instances, meta',
                ],
            ],
        ]
    )]
    public function deskpro_tool_categories(?string $category = null): string
    {
        $categories = [
            'tickets' => [
                'description' => 'Tools for ticket CRUD and search.',
                'tools' => [
                    'deskpro_get_ticket' => 'Get ticket details by ID.',
                    'deskpro_create_ticket' => 'Create a new ticket.',
                    'deskpro_update_ticket' => 'Update ticket fields.',
                    'deskpro_search_tickets' => 'Search tickets by keyword, status, agent, etc.',
                    'deskpro_list_tickets' => 'List tickets with pagination and filters.',
                ],
            ],
            'actions' => [
                'description' => 'Tools for ticket actions (replies, notes, assignments).',
                'tools' => [
                    'deskpro_add_reply' => 'Reply to a ticket (visible to user).',
                    'deskpro_add_note' => 'Add an internal agent note.',
                    'deskpro_get_messages' => 'Get messages/replies for a ticket.',
                    'deskpro_assign_ticket' => 'Assign ticket to an agent.',
                    'deskpro_change_status' => 'Change ticket status.',
                ],
            ],
            'lookups' => [
                'description' => 'Tools for looking up reference data.',
                'tools' => [
                    'deskpro_list_departments' => 'List available departments.',
                    'deskpro_list_agents' => 'List agents (support staff).',
                    'deskpro_list_statuses' => 'List ticket statuses.',
                    'deskpro_get_ticket_fields' => 'Get custom field definitions.',
                    'deskpro_list_filters' => 'List saved ticket filters/views.',
                ],
            ],
            'instances' => [
                'description' => 'Tools for managing configured Deskpro instances.',
                'tools' => [
                    'deskpro_list_instances' => 'List all configured instances.',
                    'deskpro_switch_instance' => 'Switch the active default instance.',
                ],
            ],
            'meta' => [
                'description' => 'Self-documenting tools (you are using one now).',
                'tools' => [
                    'deskpro_help' => 'Context-aware help.',
                    'deskpro_tool_categories' => 'This tool.',
                    'deskpro_usage_examples' => 'Common workflow examples.',
                    'deskpro_server_info' => 'Server version and capabilities.',
                    'deskpro_error_guide' => 'Error troubleshooting.',
                ],
            ],
        ];

        if ($category !== null && isset($categories[$category])) {
            $cat = $categories[$category];
            $lines = ["{$category}: {$cat['description']}", ""];
            foreach ($cat['tools'] as $tool => $desc) {
                $lines[] = "  - {$tool}: {$desc}";
            }
            return implode("\n", $lines);
        }

        $lines = ["Deskpro MCP Tool Categories", "===========================", ""];
        foreach ($categories as $key => $cat) {
            $count = count($cat['tools']);
            $lines[] = "  - {$key} ({$count} tools): {$cat['description']}";
        }
        $lines[] = "";
        $lines[] = "Call deskpro_tool_categories with a category parameter for tool details.";

        return implode("\n", $lines);
    }

    /**
     * Get usage examples.
     */
    #[McpTool(
        name: 'deskpro_usage_examples',
        description: 'Get common workflow examples for Deskpro operations.',
        inputSchema: [
            'type' => 'object',
            'properties' => [
                'workflow' => [
                    'type' => 'string',
                    'description' => 'Workflow: search_tickets, create_ticket, reply_to_ticket, triage_tickets',
                ],
            ],
        ]
    )]
    public function deskpro_usage_examples(?string $workflow = null): string
    {
        $workflows = [
            'search_tickets' => implode("\n", [
                "Search and filter Deskpro tickets.",
                "",
                "Steps:",
                "  1. Search by keyword: deskpro_search_tickets({\"query\": \"login issue\"})",
                "  2. Filter by status: deskpro_search_tickets({\"status\": \"awaiting_agent\"})",
                "  3. Filter by agent: deskpro_search_tickets({\"agent_id\": 123})",
                "  4. Get full details: deskpro_get_ticket({\"ticket_id\": 5702})",
                "  5. Get messages: deskpro_get_messages({\"ticket_id\": 5702})",
            ]),
            'create_ticket' => implode("\n", [
                "Create a new support ticket.",
                "",
                "Steps:",
                "  1. List departments: deskpro_list_departments()",
                "  2. Create ticket:",
                "     deskpro_create_ticket({",
                "       \"subject\": \"Password reset not working\",",
                "       \"message\": \"User reports password reset emails are not arriving.\",",
                "       \"person_email\": \"user@example.com\",",
                "       \"department_id\": 1",
                "     })",
                "  3. Optionally assign: deskpro_assign_ticket({\"ticket_id\": 123, \"agent_id\": 45})",
            ]),
            'reply_to_ticket' => implode("\n", [
                "Reply to an existing ticket.",
                "",
                "Steps:",
                "  1. Get ticket context: deskpro_get_ticket({\"ticket_id\": 5702})",
                "  2. Read messages: deskpro_get_messages({\"ticket_id\": 5702})",
                "  3. Add reply (visible to user):",
                "     deskpro_add_reply({\"ticket_id\": 5702, \"message\": \"We've resolved the issue...\"})",
                "  4. Or add internal note (agents only):",
                "     deskpro_add_note({\"ticket_id\": 5702, \"note\": \"Root cause was DNS misconfiguration.\"})",
            ]),
            'triage_tickets' => implode("\n", [
                "Triage unassigned or waiting tickets.",
                "",
                "Steps:",
                "  1. List waiting tickets: deskpro_list_tickets({\"status\": \"awaiting_agent\"})",
                "  2. List agents: deskpro_list_agents()",
                "  3. For each ticket, assign: deskpro_assign_ticket({\"ticket_id\": X, \"agent_id\": Y})",
                "  4. Change status if needed: deskpro_change_status({\"ticket_id\": X, \"status\": \"awaiting_user\"})",
            ]),
        ];

        if ($workflow !== null && isset($workflows[$workflow])) {
            return $workflows[$workflow];
        }

        $lines = ["Deskpro MCP Workflow Examples", "============================", ""];
        foreach ($workflows as $key => $content) {
            $summary = strtok($content, "\n");
            $lines[] = "  - {$key}: {$summary}";
        }
        $lines[] = "";
        $lines[] = "Call deskpro_usage_examples with a workflow parameter for step-by-step instructions.";

        return implode("\n", $lines);
    }

    /**
     * Get server info.
     */
    #[McpTool(
        name: 'deskpro_server_info',
        description: 'Get information about this Deskpro MCP server including version, capabilities, and configured instances.'
    )]
    public function deskpro_server_info(): string
    {
        $instances = $this->manager->listInstances();
        $instanceLines = [];
        foreach ($instances as $name => $info) {
            $marker = $info['is_default'] ? ' [default]' : '';
            $instanceLines[] = "    {$name}: {$info['auth_method']}{$marker}";
        }

        $lines = [
            "Deskpro MCP Server Information",
            "==============================",
            "",
            "Name:     deskpro-mcp",
            "Version:  {$this->serverVersion}",
            "Protocol: MCP 2025-03-26 (stdio)",
            "",
            "Capabilities:",
            "  - 5 Ticket tools (get, create, update, search, list)",
            "  - 5 Action tools (reply, note, messages, assign, status)",
            "  - 5 Lookup tools (departments, agents, statuses, fields, filters)",
            "  - 2 Instance management tools (list, switch)",
            "  - 5 Meta tools (help, categories, examples, info, errors)",
            "",
            "Authentication methods:",
            "  - API Key (Authorization: key <token>)",
            "  - OAuth Bearer token with auto-refresh",
            "",
            "Configured instances:",
        ];
        $lines = array_merge($lines, $instanceLines);

        return implode("\n", $lines);
    }

    /**
     * Get error guide.
     */
    #[McpTool(
        name: 'deskpro_error_guide',
        description: 'Get information about common error codes and how to resolve them.',
        inputSchema: [
            'type' => 'object',
            'properties' => [
                'error_code' => [
                    'type' => 'string',
                    'description' => 'Error code: UNAUTHORIZED, FORBIDDEN, NOT_FOUND, RATE_LIMITED, TOKEN_EXPIRED, INVALID_CONFIG',
                ],
            ],
        ]
    )]
    public function deskpro_error_guide(?string $error_code = null): string
    {
        $errors = [
            'UNAUTHORIZED' => implode("\n", [
                "Unauthorized (401)",
                "",
                "Cause: Invalid or expired authentication credentials.",
                "",
                "Resolution:",
                "  1. For API Key: verify api_token in your deskpro.json config.",
                "  2. For OAuth token: the access token may have expired. The server",
                "     auto-refreshes tokens, but if the refresh token is also expired,",
                "     you need fresh tokens from the Deskpro web app.",
                "  3. Check that the instance name is correct (deskpro_list_instances).",
            ]),
            'FORBIDDEN' => implode("\n", [
                "Forbidden (403)",
                "",
                "Cause: The authenticated user lacks permission for this operation.",
                "",
                "Resolution:",
                "  1. Verify the agent has the required permissions in Deskpro admin.",
                "  2. Some operations require admin privileges.",
                "  3. Check department-level permissions for ticket operations.",
            ]),
            'NOT_FOUND' => implode("\n", [
                "Not Found (404)",
                "",
                "Cause: The requested resource does not exist.",
                "",
                "Resolution:",
                "  1. Verify the ticket ID, agent ID, or department ID is correct.",
                "  2. The resource may have been deleted.",
                "  3. Check you're querying the correct instance (deskpro_list_instances).",
            ]),
            'RATE_LIMITED' => implode("\n", [
                "Rate Limited (429)",
                "",
                "Cause: Too many API requests in a short period.",
                "",
                "Resolution:",
                "  1. Wait a moment and retry the request.",
                "  2. Avoid making many rapid successive calls.",
                "  3. Use search with filters instead of individual gets.",
            ]),
            'TOKEN_EXPIRED' => implode("\n", [
                "Token Expired",
                "",
                "Cause: Both access and refresh tokens have expired.",
                "",
                "Resolution:",
                "  1. Log into the Deskpro web app to get fresh tokens.",
                "  2. Update access_token and refresh_token in your deskpro.json.",
                "  3. Or ask a Deskpro admin to generate an API key for stable access.",
            ]),
            'INVALID_CONFIG' => implode("\n", [
                "Invalid Configuration",
                "",
                "Cause: The deskpro.json config file is missing or malformed.",
                "",
                "Resolution:",
                "  1. Ensure deskpro.json exists in ~/.config/deskpro-mcp/ or config/.",
                "  2. Verify valid JSON syntax.",
                "  3. Check required fields: site_url, auth_method, and auth credentials.",
                "  4. See deskpro_help topic 'authentication' for config examples.",
            ]),
        ];

        if ($error_code !== null && isset($errors[$error_code])) {
            return $errors[$error_code];
        }

        $lines = ["Deskpro MCP Error Guide", "=======================", ""];
        foreach ($errors as $code => $content) {
            $summary = strtok($content, "\n");
            $lines[] = "  - {$code}: {$summary}";
        }
        $lines[] = "";
        $lines[] = "Call deskpro_error_guide with an error_code parameter for detailed resolution steps.";

        return implode("\n", $lines);
    }

    /**
     * Help topic content.
     */
    private function helpTopics(): array
    {
        return [
            'getting_started' => implode("\n", [
                "Quick start guide for the Deskpro MCP server.",
                "",
                "This server connects AI agents to Deskpro helpdesk via the",
                "Model Context Protocol. It supports multiple instances simultaneously.",
                "",
                "First steps:",
                "  1. Ensure your deskpro.json config exists with at least one instance.",
                "  2. For API Key: no auth step needed (credentials are in config).",
                "  3. For OAuth token: ensure access_token and refresh_token are set.",
                "  4. Use deskpro_list_tickets or deskpro_search_tickets to verify.",
                "",
                "Key tools:",
                "  - deskpro_search_tickets: Search tickets by keyword or filters.",
                "  - deskpro_get_ticket: Get full ticket details by ID.",
                "  - deskpro_add_reply: Reply to a ticket.",
                "  - deskpro_list_agents: List available agents.",
                "",
                "Use deskpro_tool_categories to explore all available tools.",
            ]),
            'authentication' => implode("\n", [
                "How to set up authentication for the Deskpro MCP server.",
                "",
                "Two authentication methods are supported:",
                "",
                "=== API Key (Recommended for stability) ===",
                "",
                "Requires admin access to generate a key.",
                "",
                "Config (deskpro.json):",
                "  {",
                "    \"instances\": {",
                "      \"my-helpdesk\": {",
                "        \"auth_method\": \"apikey\",",
                "        \"api_token\": \"YOUR_API_KEY\",",
                "        \"site_url\": \"https://yourcompany.deskpro.com\"",
                "      }",
                "    }",
                "  }",
                "",
                "Generate a key: Admin > Apps & Integrations > API Keys > New API Key.",
                "",
                "=== OAuth Bearer Token ===",
                "",
                "Uses JWT tokens from the Deskpro web app session. No admin access needed.",
                "Tokens auto-refresh every 3 hours via the refresh token.",
                "",
                "Config (deskpro.json):",
                "  {",
                "    \"instances\": {",
                "      \"my-helpdesk\": {",
                "        \"auth_method\": \"token\",",
                "        \"access_token\": \"eyJ...\",",
                "        \"refresh_token\": \"def502...\",",
                "        \"site_url\": \"https://yourcompany.deskpro.com\"",
                "      }",
                "    }",
                "  }",
                "",
                "Get tokens from your browser cookies (app_access_token, app_refresh_token).",
            ]),
            'multi_instance' => implode("\n", [
                "Managing multiple Deskpro instances.",
                "",
                "The server supports multiple Deskpro helpdesks simultaneously.",
                "Each instance has its own authentication.",
                "",
                "Config example:",
                "  {",
                "    \"default\": \"production\",",
                "    \"instances\": {",
                "      \"production\": { ... },",
                "      \"staging\": { ... }",
                "    }",
                "  }",
                "",
                "Usage:",
                "  - Most tools accept an optional 'instance' parameter.",
                "  - If omitted, the default instance is used.",
                "  - Use deskpro_list_instances to see all configured sites.",
                "  - Use deskpro_switch_instance to change the default.",
            ]),
            'best_practices' => implode("\n", [
                "Best practices for using the Deskpro MCP tools.",
                "",
                "Authentication:",
                "  - Prefer API Key over OAuth tokens for unattended use.",
                "  - OAuth tokens expire every 3 hours but auto-refresh.",
                "  - If a tool returns UNAUTHORIZED, check deskpro_error_guide.",
                "",
                "Tickets:",
                "  - Use deskpro_search_tickets for keyword search.",
                "  - Use deskpro_list_tickets with status filter for queues.",
                "  - Get full context before replying: deskpro_get_messages first.",
                "",
                "Performance:",
                "  - Limit results with count parameter (default 25).",
                "  - Use status and department filters to narrow results.",
                "",
                "Error handling:",
                "  - Use deskpro_error_guide for troubleshooting.",
                "  - 401 errors: check authentication config.",
                "  - 404 errors: verify the resource ID and instance.",
            ]),
        ];
    }
}
