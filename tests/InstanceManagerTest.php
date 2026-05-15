<?php
/**
 * Tests for DeskproMCP\InstanceManager: configuration, multi-instance, defaults.
 */

use PHPUnit\Framework\TestCase;
use DeskproMCP\InstanceManager;

class InstanceManagerTest extends TestCase
{
    private function makeConfig(): array
    {
        return [
            'production' => [
                'auth_method' => 'apikey',
                'api_token' => 'abc123',
                'site_url' => 'https://acme.deskpro.com',
                'description' => 'Production helpdesk',
            ],
            'staging' => [
                'auth_method' => 'token',
                'access_token' => 'eyJ...',
                'refresh_token' => 'def502...',
                'site_url' => 'https://acme-staging.deskpro.com',
                'description' => 'Staging helpdesk',
            ],
        ];
    }

    // --- Constructor validation ---

    public function testConstructorRequiresAtLeastOneInstance(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one instance must be configured');
        new InstanceManager([], 'anything');
    }

    public function testConstructorRequiresDefaultToExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Default instance 'nonexistent' not found");
        new InstanceManager($this->makeConfig(), 'nonexistent');
    }

    public function testConstructorAcceptsValidConfig(): void
    {
        $manager = new InstanceManager($this->makeConfig(), 'production');
        $this->assertInstanceOf(InstanceManager::class, $manager);
    }

    // --- getDefault / setDefault ---

    public function testGetDefaultReturnsInitialDefault(): void
    {
        $manager = new InstanceManager($this->makeConfig(), 'production');
        $this->assertEquals('production', $manager->getDefault());
    }

    public function testSetDefaultSwitchesDefault(): void
    {
        $manager = new InstanceManager($this->makeConfig(), 'production');
        $manager->setDefault('staging');
        $this->assertEquals('staging', $manager->getDefault());
    }

    public function testSetDefaultThrowsForUnknown(): void
    {
        $manager = new InstanceManager($this->makeConfig(), 'production');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown instance 'nonexistent'");
        $manager->setDefault('nonexistent');
    }

    // --- hasInstance ---

    public function testHasInstanceReturnsTrueForConfigured(): void
    {
        $manager = new InstanceManager($this->makeConfig(), 'production');
        $this->assertTrue($manager->hasInstance('production'));
        $this->assertTrue($manager->hasInstance('staging'));
    }

    public function testHasInstanceReturnsFalseForUnknown(): void
    {
        $manager = new InstanceManager($this->makeConfig(), 'production');
        $this->assertFalse($manager->hasInstance('nonexistent'));
    }

    // --- count ---

    public function testCountReturnsInstanceCount(): void
    {
        $manager = new InstanceManager($this->makeConfig(), 'production');
        $this->assertEquals(2, $manager->count());
    }

    // --- listInstances ---

    public function testListInstancesReturnsAllInstances(): void
    {
        $manager = new InstanceManager($this->makeConfig(), 'production');
        $list = $manager->listInstances();

        $this->assertArrayHasKey('production', $list);
        $this->assertArrayHasKey('staging', $list);

        $this->assertTrue($list['production']['is_default']);
        $this->assertFalse($list['staging']['is_default']);

        $this->assertEquals('apikey', $list['production']['auth_method']);
        $this->assertEquals('token', $list['staging']['auth_method']);
    }

    public function testListInstancesReflectsDefaultChange(): void
    {
        $manager = new InstanceManager($this->makeConfig(), 'production');
        $manager->setDefault('staging');
        $list = $manager->listInstances();

        $this->assertFalse($list['production']['is_default']);
        $this->assertTrue($list['staging']['is_default']);
    }

    // --- getConfig ---

    public function testGetConfigReturnsInstanceConfig(): void
    {
        $manager = new InstanceManager($this->makeConfig(), 'production');
        $config = $manager->getConfig('production');

        $this->assertEquals('apikey', $config['auth_method']);
        $this->assertEquals('abc123', $config['api_token']);
        $this->assertEquals('https://acme.deskpro.com', $config['site_url']);
    }

    public function testGetConfigDefaultsToCurrentDefault(): void
    {
        $manager = new InstanceManager($this->makeConfig(), 'staging');
        $config = $manager->getConfig();

        $this->assertEquals('token', $config['auth_method']);
    }

    public function testGetConfigThrowsForUnknown(): void
    {
        $manager = new InstanceManager($this->makeConfig(), 'production');
        $this->expectException(\InvalidArgumentException::class);
        $manager->getConfig('nonexistent');
    }

    // --- fromFile ---

    public function testFromFileThrowsForMissingFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Configuration file not found');
        InstanceManager::fromFile('/nonexistent/path/deskpro.json');
    }

    public function testFromFileThrowsForInvalidJson(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'deskpro_test_');
        file_put_contents($tmp, '{invalid json');

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Invalid JSON');
            InstanceManager::fromFile($tmp);
        } finally {
            unlink($tmp);
        }
    }

    public function testFromFileMultiInstance(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'deskpro_test_');
        file_put_contents($tmp, json_encode([
            'default' => 'prod',
            'instances' => [
                'prod' => ['auth_method' => 'apikey', 'api_token' => 'k1', 'site_url' => 'https://p.deskpro.com'],
                'dev' => ['auth_method' => 'apikey', 'api_token' => 'k2', 'site_url' => 'https://d.deskpro.com'],
            ],
        ]));

        try {
            $manager = InstanceManager::fromFile($tmp);
            $this->assertEquals('prod', $manager->getDefault());
            $this->assertEquals(2, $manager->count());
        } finally {
            unlink($tmp);
        }
    }

    public function testFromFileLegacySingleInstance(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'deskpro_test_');
        file_put_contents($tmp, json_encode([
            'auth_method' => 'apikey',
            'api_token' => 'legacy_key',
            'site_url' => 'https://mycompany.deskpro.com',
        ]));

        try {
            $manager = InstanceManager::fromFile($tmp);
            $this->assertEquals(1, $manager->count());
            $this->assertEquals('mycompany', $manager->getDefault());
        } finally {
            unlink($tmp);
        }
    }

    public function testFromFileDefaultsToFirstInstance(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'deskpro_test_');
        file_put_contents($tmp, json_encode([
            'instances' => [
                'alpha' => ['auth_method' => 'apikey', 'api_token' => 'a', 'site_url' => 'https://a.deskpro.com'],
                'beta' => ['auth_method' => 'apikey', 'api_token' => 'b', 'site_url' => 'https://b.deskpro.com'],
            ],
        ]));

        try {
            $manager = InstanceManager::fromFile($tmp);
            $this->assertEquals('alpha', $manager->getDefault());
        } finally {
            unlink($tmp);
        }
    }
}
