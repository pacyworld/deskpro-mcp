<?php
/**
 * Deskpro MCP Server - Lookup Tools
 *
 * MCP tools for looking up departments, agents, statuses, fields, and filters.
 *
 * @package    DeskproMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use DeskproMCP\InstanceManager;

/**
 * LookupTools - Reference data lookups for Deskpro.
 */
class LookupTools
{
    /** @var InstanceManager */
    private InstanceManager $manager;

    /**
     * @param InstanceManager $manager Instance manager
     */
    public function __construct(InstanceManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * List departments.
     */
    #[McpTool(
        name: 'deskpro_list_departments',
        description: 'List available Deskpro departments.',
        inputSchema: [
            'type' => 'object',
            'properties' => [
                'instance' => ['type' => 'string', 'description' => 'Instance name (optional)'],
            ],
        ]
    )]
    public function deskpro_list_departments(string $instance = ''): array
    {
        $client = $this->manager->getClient($instance ?: null);
        return $client->get('ticket_departments');
    }

    /**
     * List agents.
     */
    #[McpTool(
        name: 'deskpro_list_agents',
        description: 'List Deskpro agents (support staff).',
        inputSchema: [
            'type' => 'object',
            'properties' => [
                'instance' => ['type' => 'string', 'description' => 'Instance name (optional)'],
            ],
        ]
    )]
    public function deskpro_list_agents(string $instance = ''): array
    {
        $client = $this->manager->getClient($instance ?: null);
        return $client->get('agents');
    }

    /**
     * List ticket statuses.
     */
    #[McpTool(
        name: 'deskpro_list_statuses',
        description: 'List available Deskpro ticket statuses.',
        inputSchema: [
            'type' => 'object',
            'properties' => [
                'instance' => ['type' => 'string', 'description' => 'Instance name (optional)'],
            ],
        ]
    )]
    public function deskpro_list_statuses(string $instance = ''): array
    {
        $client = $this->manager->getClient($instance ?: null);
        return $client->get('ticket_statuses');
    }

    /**
     * Get custom field definitions.
     */
    #[McpTool(
        name: 'deskpro_get_ticket_fields',
        description: 'Get Deskpro ticket custom field definitions.',
        inputSchema: [
            'type' => 'object',
            'properties' => [
                'instance' => ['type' => 'string', 'description' => 'Instance name (optional)'],
            ],
        ]
    )]
    public function deskpro_get_ticket_fields(string $instance = ''): array
    {
        $client = $this->manager->getClient($instance ?: null);
        return $client->get('ticket_fields');
    }

    /**
     * List saved ticket filters.
     */
    #[McpTool(
        name: 'deskpro_list_filters',
        description: 'List saved Deskpro ticket filters (views).',
        inputSchema: [
            'type' => 'object',
            'properties' => [
                'instance' => ['type' => 'string', 'description' => 'Instance name (optional)'],
            ],
        ]
    )]
    public function deskpro_list_filters(string $instance = ''): array
    {
        $client = $this->manager->getClient($instance ?: null);
        return $client->get('ticket_filters');
    }
}
