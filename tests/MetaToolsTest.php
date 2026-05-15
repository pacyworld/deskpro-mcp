<?php
/**
 * Tests for MetaTools - self-documenting MCP tools.
 */

use PHPUnit\Framework\TestCase;
use DeskproMCP\InstanceManager;

class MetaToolsTest extends TestCase
{
    private MetaTools $metaTools;

    protected function setUp(): void
    {
        $config = [
            'production' => [
                'auth_method' => 'apikey',
                'api_token' => 'test-key',
                'site_url' => 'https://acme.deskpro.com',
                'description' => 'Production helpdesk',
            ],
            'staging' => [
                'auth_method' => 'token',
                'access_token' => 'eyJ...',
                'refresh_token' => 'def...',
                'site_url' => 'https://staging.deskpro.com',
                'description' => 'Staging',
            ],
        ];
        $manager = new InstanceManager($config, 'production');
        $this->metaTools = new MetaTools($manager, '1.0.0');
    }

    // --- help tool ---

    public function testHelpOverviewListsAllTopics(): void
    {
        $result = $this->metaTools->deskpro_help();
        $this->assertStringContainsString('getting_started', $result);
        $this->assertStringContainsString('authentication', $result);
        $this->assertStringContainsString('multi_instance', $result);
        $this->assertStringContainsString('best_practices', $result);
    }

    public function testHelpReturnsSpecificTopic(): void
    {
        $result = $this->metaTools->deskpro_help('getting_started');
        $this->assertStringContainsString('deskpro_search_tickets', $result);
        $this->assertStringContainsString('deskpro_get_ticket', $result);
        $this->assertStringNotContainsString('Available help topics', $result);
    }

    public function testHelpAuthenticationTopicExplainsApiKey(): void
    {
        $result = $this->metaTools->deskpro_help('authentication');
        $this->assertStringContainsString('API Key', $result);
        $this->assertStringContainsString('api_token', $result);
        $this->assertStringContainsString('OAuth Bearer Token', $result);
        $this->assertStringContainsString('refresh_token', $result);
    }

    public function testHelpMultiInstanceTopicExplainsConfig(): void
    {
        $result = $this->metaTools->deskpro_help('multi_instance');
        $this->assertStringContainsString('deskpro_list_instances', $result);
        $this->assertStringContainsString('deskpro_switch_instance', $result);
        $this->assertStringContainsString('instance', $result);
    }

    public function testHelpBestPracticesTopicGivesGuidance(): void
    {
        $result = $this->metaTools->deskpro_help('best_practices');
        $this->assertStringContainsString('deskpro_search_tickets', $result);
        $this->assertStringContainsString('deskpro_error_guide', $result);
    }

    public function testHelpUnknownTopicReturnsOverview(): void
    {
        $result = $this->metaTools->deskpro_help('nonexistent');
        $this->assertStringContainsString('Available help topics', $result);
    }

    // --- tool_categories tool ---

    public function testToolCategoriesOverviewListsAll(): void
    {
        $result = $this->metaTools->deskpro_tool_categories();
        $this->assertStringContainsString('tickets', $result);
        $this->assertStringContainsString('actions', $result);
        $this->assertStringContainsString('lookups', $result);
        $this->assertStringContainsString('instances', $result);
        $this->assertStringContainsString('meta', $result);
    }

    public function testToolCategoriesTicketsListsTools(): void
    {
        $result = $this->metaTools->deskpro_tool_categories('tickets');
        $this->assertStringContainsString('deskpro_get_ticket', $result);
        $this->assertStringContainsString('deskpro_create_ticket', $result);
        $this->assertStringContainsString('deskpro_search_tickets', $result);
        $this->assertStringContainsString('deskpro_list_tickets', $result);
        $this->assertStringContainsString('deskpro_update_ticket', $result);
    }

    public function testToolCategoriesActionsListsTools(): void
    {
        $result = $this->metaTools->deskpro_tool_categories('actions');
        $this->assertStringContainsString('deskpro_add_reply', $result);
        $this->assertStringContainsString('deskpro_add_note', $result);
        $this->assertStringContainsString('deskpro_get_messages', $result);
        $this->assertStringContainsString('deskpro_assign_ticket', $result);
        $this->assertStringContainsString('deskpro_change_status', $result);
    }

    public function testToolCategoriesLookupsListsTools(): void
    {
        $result = $this->metaTools->deskpro_tool_categories('lookups');
        $this->assertStringContainsString('deskpro_list_departments', $result);
        $this->assertStringContainsString('deskpro_list_agents', $result);
        $this->assertStringContainsString('deskpro_list_statuses', $result);
    }

    public function testToolCategoriesMetaListsTools(): void
    {
        $result = $this->metaTools->deskpro_tool_categories('meta');
        $this->assertStringContainsString('deskpro_help', $result);
        $this->assertStringContainsString('deskpro_usage_examples', $result);
        $this->assertStringContainsString('deskpro_server_info', $result);
        $this->assertStringContainsString('deskpro_error_guide', $result);
    }

    public function testToolCategoriesUnknownReturnsOverview(): void
    {
        $result = $this->metaTools->deskpro_tool_categories('nonexistent');
        $this->assertStringContainsString('Deskpro MCP Tool Categories', $result);
    }

    // --- usage_examples tool ---

    public function testUsageExamplesOverviewListsAll(): void
    {
        $result = $this->metaTools->deskpro_usage_examples();
        $this->assertStringContainsString('search_tickets', $result);
        $this->assertStringContainsString('create_ticket', $result);
        $this->assertStringContainsString('reply_to_ticket', $result);
        $this->assertStringContainsString('triage_tickets', $result);
    }

    public function testUsageExamplesSearchWorkflow(): void
    {
        $result = $this->metaTools->deskpro_usage_examples('search_tickets');
        $this->assertStringContainsString('deskpro_search_tickets', $result);
        $this->assertStringContainsString('deskpro_get_ticket', $result);
        $this->assertStringContainsString('deskpro_get_messages', $result);
    }

    public function testUsageExamplesCreateWorkflow(): void
    {
        $result = $this->metaTools->deskpro_usage_examples('create_ticket');
        $this->assertStringContainsString('deskpro_create_ticket', $result);
        $this->assertStringContainsString('deskpro_list_departments', $result);
    }

    public function testUsageExamplesReplyWorkflow(): void
    {
        $result = $this->metaTools->deskpro_usage_examples('reply_to_ticket');
        $this->assertStringContainsString('deskpro_add_reply', $result);
        $this->assertStringContainsString('deskpro_add_note', $result);
    }

    public function testUsageExamplesTriageWorkflow(): void
    {
        $result = $this->metaTools->deskpro_usage_examples('triage_tickets');
        $this->assertStringContainsString('deskpro_assign_ticket', $result);
        $this->assertStringContainsString('deskpro_change_status', $result);
    }

    public function testUsageExamplesUnknownReturnsOverview(): void
    {
        $result = $this->metaTools->deskpro_usage_examples('nonexistent');
        $this->assertStringContainsString('Deskpro MCP Workflow Examples', $result);
    }

    // --- server_info tool ---

    public function testServerInfoContainsVersion(): void
    {
        $result = $this->metaTools->deskpro_server_info();
        $this->assertStringContainsString('1.0.0', $result);
        $this->assertStringContainsString('deskpro-mcp', $result);
    }

    public function testServerInfoListsCapabilities(): void
    {
        $result = $this->metaTools->deskpro_server_info();
        $this->assertStringContainsString('5 Ticket tools', $result);
        $this->assertStringContainsString('5 Action tools', $result);
        $this->assertStringContainsString('5 Lookup tools', $result);
        $this->assertStringContainsString('2 Instance management tools', $result);
        $this->assertStringContainsString('5 Meta tools', $result);
    }

    public function testServerInfoListsInstances(): void
    {
        $result = $this->metaTools->deskpro_server_info();
        $this->assertStringContainsString('production', $result);
        $this->assertStringContainsString('staging', $result);
        $this->assertStringContainsString('apikey', $result);
    }

    public function testServerInfoShowsProtocol(): void
    {
        $result = $this->metaTools->deskpro_server_info();
        $this->assertStringContainsString('MCP 2025-03-26', $result);
        $this->assertStringContainsString('stdio', $result);
    }

    // --- error_guide tool ---

    public function testErrorGuideOverviewListsAll(): void
    {
        $result = $this->metaTools->deskpro_error_guide();
        $this->assertStringContainsString('UNAUTHORIZED', $result);
        $this->assertStringContainsString('FORBIDDEN', $result);
        $this->assertStringContainsString('NOT_FOUND', $result);
        $this->assertStringContainsString('RATE_LIMITED', $result);
        $this->assertStringContainsString('TOKEN_EXPIRED', $result);
        $this->assertStringContainsString('INVALID_CONFIG', $result);
    }

    public function testErrorGuideUnauthorized(): void
    {
        $result = $this->metaTools->deskpro_error_guide('UNAUTHORIZED');
        $this->assertStringContainsString('401', $result);
        $this->assertStringContainsString('api_token', $result);
    }

    public function testErrorGuideForbidden(): void
    {
        $result = $this->metaTools->deskpro_error_guide('FORBIDDEN');
        $this->assertStringContainsString('403', $result);
        $this->assertStringContainsString('permission', $result);
    }

    public function testErrorGuideNotFound(): void
    {
        $result = $this->metaTools->deskpro_error_guide('NOT_FOUND');
        $this->assertStringContainsString('404', $result);
        $this->assertStringContainsString('resource', $result);
    }

    public function testErrorGuideRateLimited(): void
    {
        $result = $this->metaTools->deskpro_error_guide('RATE_LIMITED');
        $this->assertStringContainsString('429', $result);
        $this->assertStringContainsString('retry', $result);
    }

    public function testErrorGuideTokenExpired(): void
    {
        $result = $this->metaTools->deskpro_error_guide('TOKEN_EXPIRED');
        $this->assertStringContainsString('refresh', $result);
        $this->assertStringContainsString('expired', $result);
    }

    public function testErrorGuideInvalidConfig(): void
    {
        $result = $this->metaTools->deskpro_error_guide('INVALID_CONFIG');
        $this->assertStringContainsString('deskpro.json', $result);
        $this->assertStringContainsString('site_url', $result);
    }

    public function testErrorGuideUnknownReturnsOverview(): void
    {
        $result = $this->metaTools->deskpro_error_guide('NONEXISTENT');
        $this->assertStringContainsString('Deskpro MCP Error Guide', $result);
    }
}
