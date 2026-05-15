<?php
/**
 * Tests for InstanceTools - MCP tools for multi-instance management.
 */

use PHPUnit\Framework\TestCase;
use DeskproMCP\InstanceManager;

class InstanceToolsTest extends TestCase
{
    private InstanceTools $instanceTools;
    private InstanceManager $manager;

    protected function setUp(): void
    {
        $config = [
            'production' => [
                'auth_method' => 'apikey',
                'api_token' => 'prod-key',
                'site_url' => 'https://acme.deskpro.com',
                'description' => 'Production',
            ],
            'staging' => [
                'auth_method' => 'token',
                'access_token' => 'eyJ...',
                'refresh_token' => 'def...',
                'site_url' => 'https://staging.deskpro.com',
                'description' => 'Staging',
            ],
        ];
        $this->manager = new InstanceManager($config, 'production');
        $this->instanceTools = new InstanceTools($this->manager);
    }

    // --- list_instances ---

    public function testListInstancesReturnsDefault(): void
    {
        $result = $this->instanceTools->deskpro_list_instances();
        $this->assertEquals('production', $result['default']);
    }

    public function testListInstancesReturnsCount(): void
    {
        $result = $this->instanceTools->deskpro_list_instances();
        $this->assertEquals(2, $result['count']);
    }

    public function testListInstancesContainsAllInstances(): void
    {
        $result = $this->instanceTools->deskpro_list_instances();
        $this->assertArrayHasKey('production', $result['instances']);
        $this->assertArrayHasKey('staging', $result['instances']);
    }

    public function testListInstancesShowsAuthMethod(): void
    {
        $result = $this->instanceTools->deskpro_list_instances();
        $this->assertEquals('apikey', $result['instances']['production']['auth_method']);
        $this->assertEquals('token', $result['instances']['staging']['auth_method']);
    }

    // --- switch_instance ---

    public function testSwitchInstanceChangesDefault(): void
    {
        $result = $this->instanceTools->deskpro_switch_instance('staging');
        $this->assertTrue($result['success']);
        $this->assertEquals('production', $result['previous_default']);
        $this->assertEquals('staging', $result['current_default']);
        $this->assertEquals('staging', $this->manager->getDefault());
    }

    public function testSwitchInstanceThrowsForUnknown(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->instanceTools->deskpro_switch_instance('nonexistent');
    }

    public function testSwitchInstanceReflectedInList(): void
    {
        $this->instanceTools->deskpro_switch_instance('staging');
        $result = $this->instanceTools->deskpro_list_instances();
        $this->assertEquals('staging', $result['default']);
        $this->assertTrue($result['instances']['staging']['is_default']);
        $this->assertFalse($result['instances']['production']['is_default']);
    }
}
