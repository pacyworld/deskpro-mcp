<?php
/**
 * Deskpro MCP Server - REST API Client
 *
 * HTTP client wrapper for Deskpro REST API v2 with dual auth
 * (API Key and OAuth Bearer token with auto-refresh).
 *
 * @package    DeskproMCP\DeskproMCP
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

namespace DeskproMCP;

/**
 * DeskproClient - Deskpro REST API v2 HTTP client.
 *
 * Wraps EnchiladaHTTP with pluggable authentication supporting
 * both API Key and OAuth Bearer token modes.
 */
class DeskproClient
{
    /** @var \EnchiladaHTTP */
    private \EnchiladaHTTP $http;

    /** @var string */
    private string $siteUrl;

    /** @var string */
    private string $authMethod;

    /** @var string API key for apikey auth */
    private string $apiKey = '';

    /** @var string Bearer token for token auth */
    private string $accessToken = '';

    /** @var string Refresh token for token auth */
    private string $refreshToken = '';

    /** @var int Token expiry timestamp */
    private int $tokenExpiry = 0;

    /** @var string|null Path to config file for persisting refreshed tokens */
    private ?string $configPath;

    /** @var string|null Path to external token file (hot-reloaded on each request) */
    private ?string $tokenFile;

    /** @var string Instance name (for config persistence) */
    private string $instanceName;

    /**
     * Create a new Deskpro client.
     *
     * @param array  $config       Instance configuration
     * @param string $instanceName Instance name for token persistence
     * @param string|null $configPath Path to config file for persisting refreshed tokens
     */
    public function __construct(array $config, string $instanceName = 'default', ?string $configPath = null)
    {
        $this->siteUrl = rtrim($config['site_url'] ?? '', '/');
        $this->authMethod = $config['auth_method'] ?? 'apikey';
        $this->instanceName = $instanceName;
        $this->configPath = $configPath;
        $this->tokenFile = $config['token_file']
            ?? ($this->authMethod === 'token' && $configPath
                ? dirname($configPath) . '/tokens.json'
                : null);

        if ($this->authMethod === 'apikey') {
            $this->apiKey = $config['api_token'] ?? '';
            $this->accessToken = '';
            $this->refreshToken = '';
        } else {
            $this->apiKey = '';
            $this->loadTokens($config);
        }

        $this->http = new \EnchiladaHTTP($this->siteUrl . '/api/v2');
        $this->http->setTimeout(30);
    }

    /**
     * Perform an authenticated GET request.
     *
     * @param string     $endpoint API path relative to /api/v2/
     * @param array|null $params   Optional query parameters
     * @return array|false         Decoded JSON response or false on failure
     */
    public function get(string $endpoint, ?array $params = null)
    {
        $this->ensureToken();
        $headers = $this->buildHeaders();
        return $this->http->call($endpoint, $params, 'GET', $headers);
    }

    /**
     * Perform an authenticated POST request.
     *
     * @param string     $endpoint API path relative to /api/v2/
     * @param array|null $data     Request body data
     * @return array|false         Decoded JSON response or false on failure
     */
    public function post(string $endpoint, ?array $data = null)
    {
        $this->ensureToken();
        $headers = $this->buildHeaders();
        return $this->http->call($endpoint, $data, 'POST', $headers);
    }

    /**
     * Perform an authenticated PUT request.
     *
     * @param string     $endpoint API path relative to /api/v2/
     * @param array|null $data     Request body data
     * @return array|false         Decoded JSON response or false on failure
     */
    public function put(string $endpoint, ?array $data = null)
    {
        $this->ensureToken();
        $headers = $this->buildHeaders();
        $result = $this->http->call($endpoint, $data, 'PUT', $headers);
        // Deskpro returns 204 No Content on successful PUT (empty body).
        if ($result === false && $this->http->getHttpCode() >= 200 && $this->http->getHttpCode() < 300) {
            return ['success' => true];
        }
        return $result;
    }

    /**
     * Perform an authenticated DELETE request.
     *
     * @param string $endpoint API path relative to /api/v2/
     * @return array|false     Decoded JSON response or false on failure
     */
    public function delete(string $endpoint)
    {
        $this->ensureToken();
        $headers = $this->buildHeaders();
        return $this->http->call($endpoint, null, 'DELETE', $headers);
    }

    /**
     * Get the HTTP status code from the last request.
     *
     * @return int HTTP status code
     */
    public function getLastHttpCode(): int
    {
        return $this->http->getHttpCode();
    }

    /**
     * Get the site URL.
     *
     * @return string
     */
    public function getSiteUrl(): string
    {
        return $this->siteUrl;
    }

    /**
     * Get the auth method.
     *
     * @return string
     */
    public function getAuthMethod(): string
    {
        return $this->authMethod;
    }

    /**
     * Check if the client has valid credentials.
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        if ($this->authMethod === 'apikey') {
            return !empty($this->apiKey) && !empty($this->siteUrl);
        }
        return !empty($this->accessToken) && !empty($this->refreshToken) && !empty($this->siteUrl);
    }

    /**
     * Build authorization headers for the current auth method.
     *
     * @return array Headers array
     */
    private function buildHeaders(): array
    {
        $headers = ['Accept: application/json'];

        if ($this->authMethod === 'apikey') {
            $headers[] = 'Authorization: key ' . $this->apiKey;
        } else {
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
        }

        return $headers;
    }

    /**
     * Parse the JWT access token to extract expiry time.
     */
    private function parseTokenExpiry(): void
    {
        if (empty($this->accessToken)) {
            $this->tokenExpiry = 0;
            return;
        }

        $parts = explode('.', $this->accessToken);
        if (count($parts) !== 3) {
            $this->tokenExpiry = 0;
            return;
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        $this->tokenExpiry = (int) ($payload['exp'] ?? 0);
    }

    /**
     * Ensure the access token is valid, refreshing if needed.
     *
     * Hot-reloads tokens from external token_file if configured,
     * allowing updates without server restart.
     *
     * @throws \RuntimeException If token refresh fails
     */
    private function ensureToken(): void
    {
        if ($this->authMethod !== 'token') {
            return;
        }

        // Hot-reload from external token file (allows live updates without restart)
        if ($this->tokenFile !== null) {
            $this->reloadTokenFile();
        }

        // Refresh if token expires within 5 minutes
        if ($this->tokenExpiry > 0 && $this->tokenExpiry < (time() + 300)) {
            if (!$this->refreshAccessToken()) {
                throw new \RuntimeException(
                    'Token refresh failed. The refresh token is likely expired or was '
                    . 'invalidated by a browser session refresh (Deskpro rotates refresh '
                    . 'tokens on use - if the browser refreshed first, this token is dead). '
                    . 'Fix: close the Deskpro browser tab to prevent token races, then update '
                    . ($this->tokenFile
                        ? $this->tokenFile
                        : 'the config file'
                    )
                    . ' with fresh tokens from a new browser login '
                    . '(capture HAR of POST /agent-api/authenticate/refresh).'
                );
            }
        }
    }

    /**
     * Refresh the OAuth access token using the refresh token.
     *
     * @return bool True if refresh succeeded, false otherwise
     */
    private function refreshAccessToken(): bool
    {
        if (empty($this->refreshToken)) {
            fwrite(STDERR, "[deskpro-mcp] WARNING: No refresh token available, cannot refresh.\n");
            return false;
        }

        fwrite(STDERR, "[deskpro-mcp] Refreshing access token...\n");

        $refreshHttp = new \EnchiladaHTTP($this->siteUrl);
        $refreshHttp->setTimeout(15);

        $result = $refreshHttp->call(
            'agent-api/authenticate/refresh',
            [
                'access_token' => $this->accessToken,
                'refresh_token' => 'COOKIE',
                'isSession' => false,
            ],
            'POST',
            ['Content-Type: application/json', 'Cookie: app_refresh_token=' . $this->refreshToken]
        );

        if (is_array($result) && !empty($result['access_token'])) {
            $this->accessToken = $result['access_token'];
            if (!empty($result['refresh_token']) && $result['refresh_token'] !== 'COOKIE') {
                $this->refreshToken = $result['refresh_token'];
            }
            $this->parseTokenExpiry();
            $this->persistTokens();
            fwrite(STDERR, "[deskpro-mcp] Token refreshed successfully (expires in " . ($result['expires_in'] ?? '?') . "s).\n");
            return true;
        }

        fwrite(STDERR, "[deskpro-mcp] ERROR: Token refresh failed. Response: " . json_encode($result) . "\n");
        return false;
    }

    /**
     * Load tokens from config or external token file.
     *
     * @param array $config Instance configuration
     */
    private function loadTokens(array $config): void
    {
        if ($this->tokenFile !== null && file_exists($this->tokenFile)) {
            $this->reloadTokenFile();
        } else {
            $this->accessToken = $config['access_token'] ?? '';
            $this->refreshToken = $config['refresh_token'] ?? '';
            $this->parseTokenExpiry();
        }
    }

    /**
     * Hot-reload tokens from the external token file.
     *
     * This is called before every API request when token_file is configured,
     * allowing the user or another process to update tokens without restarting
     * the MCP server.
     */
    private function reloadTokenFile(): void
    {
        if ($this->tokenFile === null || !file_exists($this->tokenFile)) {
            return;
        }

        $json = file_get_contents($this->tokenFile);
        $tokens = json_decode($json, true);
        if ($tokens === null) {
            return;
        }

        $newAccess = $tokens['access_token'] ?? '';
        $newRefresh = $tokens['refresh_token'] ?? '';

        // Only re-parse if tokens actually changed
        if ($newAccess !== $this->accessToken || $newRefresh !== $this->refreshToken) {
            $this->accessToken = $newAccess;
            $this->refreshToken = $newRefresh;
            $this->parseTokenExpiry();
            fwrite(STDERR, "[deskpro-mcp] Tokens reloaded from {$this->tokenFile}\n");
        }
    }

    /**
     * Persist refreshed tokens back to storage.
     *
     * Writes to the external token file if configured, otherwise updates
     * the main config file.
     */
    private function persistTokens(): void
    {
        // Prefer external token file
        if ($this->tokenFile !== null) {
            $tokens = [
                'access_token' => $this->accessToken,
                'refresh_token' => $this->refreshToken,
            ];
            file_put_contents(
                $this->tokenFile,
                json_encode($tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
            );
            return;
        }

        // Fall back to config file
        if ($this->configPath === null || !file_exists($this->configPath)) {
            return;
        }

        $json = file_get_contents($this->configPath);
        $config = json_decode($json, true);
        if ($config === null) {
            return;
        }

        if (isset($config['instances'][$this->instanceName])) {
            $config['instances'][$this->instanceName]['access_token'] = $this->accessToken;
            $config['instances'][$this->instanceName]['refresh_token'] = $this->refreshToken;
        } elseif (!isset($config['instances'])) {
            // Legacy single-instance format
            $config['access_token'] = $this->accessToken;
            $config['refresh_token'] = $this->refreshToken;
        }

        file_put_contents($this->configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    }
}
