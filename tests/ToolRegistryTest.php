<?php
/**
 * Tests for MCP ToolRegistry: attribute discovery, listing, invocation.
 */

use PHPUnit\Framework\TestCase;
use EnchiladaMCP\ToolRegistry;
use EnchiladaMCP\McpTool;

class ToolRegistryTest extends TestCase
{
    private ToolRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new ToolRegistry();
    }

    // --- Registration & Discovery ---

    public function testRegisterDiscoversMcpToolAttributes(): void
    {
        $this->registry->register(new RegistryFixture());
        $tools = $this->registry->listTools();

        $names = array_column($tools, 'name');
        $this->assertContains('fixture_greet', $names);
        $this->assertContains('fixture_optional', $names);
    }

    public function testNonAnnotatedMethodsAreIgnored(): void
    {
        $this->registry->register(new RegistryFixture());
        $names = array_column($this->registry->listTools(), 'name');
        $this->assertNotContains('helperMethod', $names);
    }

    public function testToolDescriptionFromAttribute(): void
    {
        $this->registry->register(new RegistryFixture());
        $tools = $this->registry->listTools();
        $greet = array_values(array_filter($tools, fn($t) => $t['name'] === 'fixture_greet'));
        $this->assertEquals('Greet someone.', $greet[0]['description']);
    }

    public function testToolSchemaFromAttribute(): void
    {
        $this->registry->register(new RegistryFixture());
        $tools = $this->registry->listTools();
        $greet = array_values(array_filter($tools, fn($t) => $t['name'] === 'fixture_greet'));
        $schema = $greet[0]['inputSchema'];
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertContains('name', $schema['required']);
    }

    public function testAutoSchemaGeneration(): void
    {
        $this->registry->register(new AutoSchemaFixture());
        $tools = $this->registry->listTools();
        $this->assertCount(1, $tools);

        $schema = $tools[0]['inputSchema'];
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('query', $schema['properties']);
        $this->assertEquals('string', $schema['properties']['query']['type']);
        $this->assertArrayHasKey('limit', $schema['properties']);
        $this->assertEquals('integer', $schema['properties']['limit']['type']);
        $this->assertContains('query', $schema['required']);
        $this->assertNotContains('limit', $schema['required']);
    }

    // --- hasTool ---

    public function testHasToolReturnsTrueForRegistered(): void
    {
        $this->registry->register(new RegistryFixture());
        $this->assertTrue($this->registry->hasTool('fixture_greet'));
    }

    public function testHasToolReturnsFalseForUnknown(): void
    {
        $this->assertFalse($this->registry->hasTool('nonexistent'));
    }

    // --- callTool ---

    public function testCallToolWithRequiredArgs(): void
    {
        $this->registry->register(new RegistryFixture());
        $result = $this->registry->callTool('fixture_greet', ['name' => 'World']);
        $this->assertEquals(['greeting' => 'Hello, World!'], $result);
    }

    public function testCallToolWithOptionalArgs(): void
    {
        $this->registry->register(new RegistryFixture());
        $result = $this->registry->callTool('fixture_optional', ['value' => 'x']);
        $this->assertEquals(['value' => 'x', 'flag' => false], $result);

        $result2 = $this->registry->callTool('fixture_optional', ['value' => 'x', 'flag' => true]);
        $this->assertEquals(['value' => 'x', 'flag' => true], $result2);
    }

    public function testCallToolMissingRequiredArgThrows(): void
    {
        $this->registry->register(new RegistryFixture());
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required argument: name');
        $this->registry->callTool('fixture_greet', []);
    }

    public function testCallToolUnknownToolThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->registry->callTool('nonexistent', []);
    }

    // --- Multiple registrations ---

    public function testMultipleHandlerRegistration(): void
    {
        $this->registry->register(new RegistryFixture());
        $this->registry->register(new AutoSchemaFixture());
        $tools = $this->registry->listTools();
        $names = array_column($tools, 'name');
        $this->assertContains('fixture_greet', $names);
        $this->assertContains('auto_search', $names);
    }
}

// --- Test fixtures ---

class RegistryFixture
{
    #[McpTool(
        name: 'fixture_greet',
        description: 'Greet someone.',
        inputSchema: [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string', 'description' => 'Name to greet'],
            ],
            'required' => ['name'],
        ]
    )]
    public function fixture_greet(string $name): array
    {
        return ['greeting' => "Hello, {$name}!"];
    }

    #[McpTool(
        name: 'fixture_optional',
        description: 'Test optional args.',
        inputSchema: [
            'type' => 'object',
            'properties' => [
                'value' => ['type' => 'string'],
                'flag' => ['type' => 'boolean'],
            ],
            'required' => ['value'],
        ]
    )]
    public function fixture_optional(string $value, bool $flag = false): array
    {
        return ['value' => $value, 'flag' => $flag];
    }

    public function helperMethod(): void
    {
        // No McpTool attribute - should not be discovered
    }
}

class AutoSchemaFixture
{
    #[McpTool(description: 'Auto-schema test')]
    public function auto_search(string $query, int $limit = 10): array
    {
        return ['query' => $query, 'limit' => $limit];
    }
}
