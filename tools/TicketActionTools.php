<?php
/**
 * Deskpro MCP Server - Ticket Action Tools
 *
 * MCP tools for ticket actions: replies, notes, assignments, status changes.
 *
 * @package    DeskproMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use DeskproMCP\InstanceManager;

/**
 * TicketActionTools - Ticket action operations for Deskpro.
 */
class TicketActionTools
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
     * Reply to a ticket.
     */
    #[McpTool(
        name: 'deskpro_add_reply',
        description: 'Add a reply to a Deskpro ticket. The reply is visible to the user.',
        inputSchema: [
            'type' => 'object',
            'properties' => [
                'ticket_id' => ['type' => 'integer', 'description' => 'Ticket ID'],
                'message' => ['type' => 'string', 'description' => 'Reply message body (HTML or plain text)'],
                'instance' => ['type' => 'string', 'description' => 'Instance name (optional)'],
            ],
            'required' => ['ticket_id', 'message'],
        ]
    )]
    public function deskpro_add_reply(int $ticket_id, string $message, string $instance = ''): array
    {
        $client = $this->manager->getClient($instance ?: null);
        return $client->post("tickets/{$ticket_id}/messages", [
            'message' => $message,
        ]);
    }

    /**
     * Add an internal note to a ticket.
     */
    #[McpTool(
        name: 'deskpro_add_note',
        description: 'Add an internal note to a Deskpro ticket. Notes are only visible to agents.',
        inputSchema: [
            'type' => 'object',
            'properties' => [
                'ticket_id' => ['type' => 'integer', 'description' => 'Ticket ID'],
                'note' => ['type' => 'string', 'description' => 'Note content (HTML or plain text)'],
                'instance' => ['type' => 'string', 'description' => 'Instance name (optional)'],
            ],
            'required' => ['ticket_id', 'note'],
        ]
    )]
    public function deskpro_add_note(int $ticket_id, string $note, string $instance = ''): array
    {
        $client = $this->manager->getClient($instance ?: null);
        return $client->post("tickets/{$ticket_id}/notes", [
            'note' => $note,
        ]);
    }

    /**
     * Get messages for a ticket.
     */
    #[McpTool(
        name: 'deskpro_get_messages',
        description: 'Get messages (replies and notes) for a Deskpro ticket.',
        inputSchema: [
            'type' => 'object',
            'properties' => [
                'ticket_id' => ['type' => 'integer', 'description' => 'Ticket ID'],
                'page' => ['type' => 'integer', 'description' => 'Page number (default 1)'],
                'count' => ['type' => 'integer', 'description' => 'Results per page (default 25)'],
                'instance' => ['type' => 'string', 'description' => 'Instance name (optional)'],
            ],
            'required' => ['ticket_id'],
        ]
    )]
    public function deskpro_get_messages(int $ticket_id, int $page = 1, int $count = 25, string $instance = ''): array
    {
        $client = $this->manager->getClient($instance ?: null);
        return $client->get("tickets/{$ticket_id}/messages", [
            'page' => max(1, $page),
            'count' => min(200, max(1, $count)),
        ]);
    }

    /**
     * Assign a ticket to an agent.
     */
    #[McpTool(
        name: 'deskpro_assign_ticket',
        description: 'Assign a Deskpro ticket to an agent.',
        inputSchema: [
            'type' => 'object',
            'properties' => [
                'ticket_id' => ['type' => 'integer', 'description' => 'Ticket ID'],
                'agent_id' => ['type' => 'integer', 'description' => 'Agent ID to assign the ticket to'],
                'instance' => ['type' => 'string', 'description' => 'Instance name (optional)'],
            ],
            'required' => ['ticket_id', 'agent_id'],
        ]
    )]
    public function deskpro_assign_ticket(int $ticket_id, int $agent_id, string $instance = ''): array
    {
        $client = $this->manager->getClient($instance ?: null);
        return $client->put("tickets/{$ticket_id}", [
            'agent_id' => $agent_id,
        ]);
    }

    /**
     * Change ticket status.
     */
    #[McpTool(
        name: 'deskpro_change_status',
        description: 'Change the status of a Deskpro ticket.',
        inputSchema: [
            'type' => 'object',
            'properties' => [
                'ticket_id' => ['type' => 'integer', 'description' => 'Ticket ID'],
                'status' => ['type' => 'string', 'description' => 'New status: awaiting_user, awaiting_agent, resolved, closed'],
                'instance' => ['type' => 'string', 'description' => 'Instance name (optional)'],
            ],
            'required' => ['ticket_id', 'status'],
        ]
    )]
    public function deskpro_change_status(int $ticket_id, string $status, string $instance = ''): array
    {
        $client = $this->manager->getClient($instance ?: null);
        return $client->put("tickets/{$ticket_id}", [
            'status' => $status,
        ]);
    }
}
