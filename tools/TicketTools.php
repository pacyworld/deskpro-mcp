<?php
/**
 * Deskpro MCP Server - Ticket Tools
 *
 * MCP tools for ticket CRUD and search operations.
 *
 * @package    DeskproMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use DeskproMCP\InstanceManager;

/**
 * TicketTools - Core ticket operations for Deskpro.
 */
class TicketTools
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
     * Get a ticket by ID.
     */
    #[McpTool(
        name: 'deskpro_get_ticket',
        description: 'Get a Deskpro ticket by ID. Returns ticket details including subject, status, agent, department, and messages.',
        inputSchema: [
            'type' => 'object',
            'properties' => [
                'ticket_id' => ['type' => 'integer', 'description' => 'Ticket ID'],
                'instance' => ['type' => 'string', 'description' => 'Instance name (optional, uses default if omitted)'],
            ],
            'required' => ['ticket_id'],
        ]
    )]
    public function deskpro_get_ticket(int $ticket_id, string $instance = ''): array
    {
        $client = $this->manager->getClient($instance ?: null);
        $ticket = $client->get("tickets/{$ticket_id}");

        if (!is_array($ticket) || isset($ticket['status']) && $ticket['status'] === 404) {
            return ['error' => "Ticket #{$ticket_id} not found."];
        }

        return $ticket;
    }

    /**
     * Create a new ticket.
     */
    #[McpTool(
        name: 'deskpro_create_ticket',
        description: 'Create a new Deskpro ticket.',
        inputSchema: [
            'type' => 'object',
            'properties' => [
                'subject' => ['type' => 'string', 'description' => 'Ticket subject'],
                'message' => ['type' => 'string', 'description' => 'Initial message body (HTML or plain text)'],
                'person_email' => ['type' => 'string', 'description' => 'Email of the person (requester) for the ticket'],
                'department_id' => ['type' => 'integer', 'description' => 'Department ID (optional)'],
                'priority' => ['type' => 'string', 'description' => 'Priority (optional)'],
                'status' => ['type' => 'string', 'description' => 'Status: awaiting_user, awaiting_agent, resolved, closed (optional)'],
                'agent_id' => ['type' => 'integer', 'description' => 'Assign to agent ID (optional)'],
                'instance' => ['type' => 'string', 'description' => 'Instance name (optional)'],
            ],
            'required' => ['subject', 'message'],
        ]
    )]
    public function deskpro_create_ticket(
        string $subject,
        string $message,
        string $person_email = '',
        int $department_id = 0,
        string $priority = '',
        string $status = '',
        int $agent_id = 0,
        string $instance = ''
    ): array {
        $client = $this->manager->getClient($instance ?: null);

        $data = [
            'subject' => $subject,
            'message' => ['message' => $message],
        ];

        if (!empty($person_email)) {
            $data['person'] = ['email' => $person_email];
        }
        if ($department_id > 0) {
            $data['department_id'] = $department_id;
        }
        if (!empty($priority)) {
            $data['priority'] = $priority;
        }
        if (!empty($status)) {
            $data['status'] = $status;
        }
        if ($agent_id > 0) {
            $data['agent_id'] = $agent_id;
        }

        return $client->post('tickets', $data);
    }

    /**
     * Update an existing ticket.
     */
    #[McpTool(
        name: 'deskpro_update_ticket',
        description: 'Update a Deskpro ticket. Only provided fields are updated.',
        inputSchema: [
            'type' => 'object',
            'properties' => [
                'ticket_id' => ['type' => 'integer', 'description' => 'Ticket ID to update'],
                'subject' => ['type' => 'string', 'description' => 'New subject (optional)'],
                'status' => ['type' => 'string', 'description' => 'New status: awaiting_user, awaiting_agent, resolved, closed (optional)'],
                'priority' => ['type' => 'string', 'description' => 'New priority (optional)'],
                'department_id' => ['type' => 'integer', 'description' => 'New department ID (optional)'],
                'agent_id' => ['type' => 'integer', 'description' => 'New agent ID (optional)'],
                'instance' => ['type' => 'string', 'description' => 'Instance name (optional)'],
            ],
            'required' => ['ticket_id'],
        ]
    )]
    public function deskpro_update_ticket(
        int $ticket_id,
        string $subject = '',
        string $status = '',
        string $priority = '',
        int $department_id = 0,
        int $agent_id = 0,
        string $instance = ''
    ): array {
        $client = $this->manager->getClient($instance ?: null);

        $data = [];
        if (!empty($subject)) $data['subject'] = $subject;
        if (!empty($status)) $data['status'] = $status;
        if (!empty($priority)) $data['priority'] = $priority;
        if ($department_id > 0) $data['department_id'] = $department_id;
        if ($agent_id > 0) $data['agent_id'] = $agent_id;

        if (empty($data)) {
            return ['error' => 'No fields to update.'];
        }

        return $client->put("tickets/{$ticket_id}", $data);
    }

    /**
     * Search tickets.
     */
    #[McpTool(
        name: 'deskpro_search_tickets',
        description: 'Search Deskpro tickets by keyword, status, agent, department, or date range.',
        inputSchema: [
            'type' => 'object',
            'properties' => [
                'query' => ['type' => 'string', 'description' => 'Search keyword/phrase'],
                'status' => ['type' => 'string', 'description' => 'Filter by status: awaiting_user, awaiting_agent, resolved, closed (optional)'],
                'agent_id' => ['type' => 'integer', 'description' => 'Filter by agent ID (optional)'],
                'department_id' => ['type' => 'integer', 'description' => 'Filter by department ID (optional)'],
                'page' => ['type' => 'integer', 'description' => 'Page number (optional, default 1)'],
                'count' => ['type' => 'integer', 'description' => 'Results per page (optional, default 25, max 200)'],
                'order_by' => ['type' => 'string', 'description' => 'Sort field (optional, e.g. date_created, date_last_agent_reply)'],
                'order_dir' => ['type' => 'string', 'description' => 'Sort direction: asc or desc (optional, default desc)'],
                'instance' => ['type' => 'string', 'description' => 'Instance name (optional)'],
            ],
        ]
    )]
    public function deskpro_search_tickets(
        string $query = '',
        string $status = '',
        int $agent_id = 0,
        int $department_id = 0,
        int $page = 1,
        int $count = 25,
        string $order_by = '',
        string $order_dir = 'desc',
        string $instance = ''
    ): array {
        $client = $this->manager->getClient($instance ?: null);

        $params = [
            'page' => max(1, $page),
            'count' => min(200, max(1, $count)),
        ];

        if (!empty($query)) $params['q'] = $query;
        if (!empty($status)) $params['status'] = $status;
        if ($agent_id > 0) $params['agent'] = $agent_id;
        if ($department_id > 0) $params['department'] = $department_id;
        if (!empty($order_by)) $params['order_by'] = $order_by;
        if (!empty($order_dir)) $params['order_dir'] = $order_dir;

        return $client->get('tickets', $params);
    }

    /**
     * List tickets with optional filters.
     */
    #[McpTool(
        name: 'deskpro_list_tickets',
        description: 'List Deskpro tickets with pagination and optional filters.',
        inputSchema: [
            'type' => 'object',
            'properties' => [
                'status' => ['type' => 'string', 'description' => 'Filter by status (optional)'],
                'agent_id' => ['type' => 'integer', 'description' => 'Filter by assigned agent ID (optional)'],
                'department_id' => ['type' => 'integer', 'description' => 'Filter by department ID (optional)'],
                'page' => ['type' => 'integer', 'description' => 'Page number (default 1)'],
                'count' => ['type' => 'integer', 'description' => 'Results per page (default 25, max 200)'],
                'order_by' => ['type' => 'string', 'description' => 'Sort field (optional)'],
                'order_dir' => ['type' => 'string', 'description' => 'Sort direction: asc or desc (default desc)'],
                'instance' => ['type' => 'string', 'description' => 'Instance name (optional)'],
            ],
        ]
    )]
    public function deskpro_list_tickets(
        string $status = '',
        int $agent_id = 0,
        int $department_id = 0,
        int $page = 1,
        int $count = 25,
        string $order_by = '',
        string $order_dir = 'desc',
        string $instance = ''
    ): array {
        $client = $this->manager->getClient($instance ?: null);

        $params = [
            'page' => max(1, $page),
            'count' => min(200, max(1, $count)),
        ];

        if (!empty($status)) $params['status'] = $status;
        if ($agent_id > 0) $params['agent'] = $agent_id;
        if ($department_id > 0) $params['department'] = $department_id;
        if (!empty($order_by)) $params['order_by'] = $order_by;
        if (!empty($order_dir)) $params['order_dir'] = $order_dir;

        return $client->get('tickets', $params);
    }
}
