<?php
/**
 * Deskpro MCP Server - Instance Manager
 *
 * Multi-instance configuration registry and client factory.
 *
 * @package    DeskproMCP\DeskproMCP
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

namespace DeskproMCP;

/**
 * InstanceManager - Multi-instance Deskpro registry.
 *
 * Loads instance configurations from a JSON file and provides
 * DeskproClient instances for named Deskpro helpdesks.
 */
class InstanceManager
{
    /** @var array<string,array> Instance configurations indexed by name */
    private array $instances;

    /** @var string Name of the current default instance */
    private string $default;

    /** @var array<string,DeskproClient> Cache of clients indexed by instance name */
    private array $clients = [];

    /** @var string|null Path to config file (for token persistence) */
    private ?string $configPath;

    /**
     * Create a new InstanceManager.
     *
     * @param array<string,array> $instances   Instance configurations
     * @param string              $default     Default instance name
     * @param string|null         $configPath  Path to config file
     */
    public function __construct(array $instances, string $default, ?string $configPath = null)
    {
        if (empty($instances)) {
            throw new \InvalidArgumentException('At least one instance must be configured.');
        }

        if (!isset($instances[$default])) {
            throw new \InvalidArgumentException("Default instance '{$default}' not found in configuration.");
        }

        $this->instances = $instances;
        $this->default = $default;
        $this->configPath = $configPath;
    }

    /**
     * Create an InstanceManager from a JSON configuration file.
     *
     * Automatically detects legacy single-instance format (no "instances" key)
     * and wraps it into the multi-instance format transparently.
     *
     * @param  string $path Path to deskpro.json
     * @return self
     * @throws \RuntimeException If file cannot be read or parsed
     */
    public static function fromFile(string $path): self
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Configuration file not found: {$path}");
        }

        $json = file_get_contents($path);
        if ($json === false) {
            throw new \RuntimeException("Failed to read configuration file: {$path}");
        }

        $config = json_decode($json, true);
        if ($config === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(
                "Invalid JSON in configuration file {$path}: " . json_last_error_msg()
            );
        }

        // Detect legacy single-instance format (no "instances" key)
        if (!isset($config['instances'])) {
            $name = self::deriveLegacyName($config);
            return new self([$name => $config], $name, $path);
        }

        $instances = $config['instances'] ?? [];
        $default = $config['default'] ?? '';

        if (empty($default) && !empty($instances)) {
            $default = array_key_first($instances);
        }

        return new self($instances, $default, $path);
    }

    /**
     * Derive a human-readable instance name from a legacy flat config.
     *
     * @param  array $config Legacy configuration array
     * @return string        Derived instance name
     */
    private static function deriveLegacyName(array $config): string
    {
        if (!empty($config['site_url'])) {
            $host = parse_url($config['site_url'], PHP_URL_HOST);
            if ($host && preg_match('/^([^.]+)\.deskpro\.com$/', $host, $m)) {
                return $m[1];
            }
            if ($host) {
                return $host;
            }
        }

        return 'default';
    }

    /**
     * Get a DeskproClient for the named instance (or default).
     *
     * Clients are cached - the same instance is returned for repeated calls.
     *
     * @param  string|null $name Instance name (null = default)
     * @return DeskproClient
     * @throws \InvalidArgumentException If instance not found
     */
    public function getClient(?string $name = null): DeskproClient
    {
        $name = $name ?: $this->default;

        if (!isset($this->instances[$name])) {
            $available = implode(', ', array_keys($this->instances));
            throw new \InvalidArgumentException(
                "Unknown instance '{$name}'. Available: {$available}"
            );
        }

        if (!isset($this->clients[$name])) {
            $this->clients[$name] = new DeskproClient(
                $this->instances[$name],
                $name,
                $this->configPath
            );
        }

        return $this->clients[$name];
    }

    /**
     * Get the raw configuration for an instance.
     *
     * @param  string|null $name Instance name (null = default)
     * @return array              Instance configuration
     */
    public function getConfig(?string $name = null): array
    {
        $name = $name ?: $this->default;

        if (!isset($this->instances[$name])) {
            $available = implode(', ', array_keys($this->instances));
            throw new \InvalidArgumentException(
                "Unknown instance '{$name}'. Available: {$available}"
            );
        }

        return $this->instances[$name];
    }

    /**
     * List all configured instances.
     *
     * @return array<string,array{description:string,auth_method:string,is_default:bool}>
     */
    public function listInstances(): array
    {
        $result = [];
        foreach ($this->instances as $name => $config) {
            $result[$name] = [
                'description' => $config['description'] ?? '',
                'auth_method' => $config['auth_method'] ?? 'apikey',
                'is_default' => ($name === $this->default),
            ];
        }
        return $result;
    }

    /**
     * Get the current default instance name.
     *
     * @return string Default instance name
     */
    public function getDefault(): string
    {
        return $this->default;
    }

    /**
     * Set the default instance (runtime only, not persisted).
     *
     * @param  string $name Instance name to set as default
     * @throws \InvalidArgumentException If instance not found
     */
    public function setDefault(string $name): void
    {
        if (!isset($this->instances[$name])) {
            $available = implode(', ', array_keys($this->instances));
            throw new \InvalidArgumentException(
                "Unknown instance '{$name}'. Available: {$available}"
            );
        }

        $this->default = $name;
    }

    /**
     * Check if an instance exists.
     *
     * @param  string $name Instance name
     * @return bool         True if instance is configured
     */
    public function hasInstance(string $name): bool
    {
        return isset($this->instances[$name]);
    }

    /**
     * Get the number of configured instances.
     *
     * @return int Instance count
     */
    public function count(): int
    {
        return count($this->instances);
    }
}
