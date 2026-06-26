<?php

namespace App\Integrations\MikroTik;

use App\Models\Router;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MikroTikClient
{
    private string $baseUrl;

    private string $username;

    private string $password;

    private int $timeout;

    private int $retryTimes;

    private int $retrySleep;

    public function __construct(private Router $router)
    {
        $this->baseUrl = sprintf(
            'http://%s:%d/rest/',
            $router->ip_address,
            $router->api_port ?: config('mikrotik.default_port', 8728)
        );

        $this->username = $router->username;
        $this->password = $router->getDecryptedPassword();
        $this->timeout = config('mikrotik.timeout', 10);
        $this->retryTimes = config('mikrotik.retry_times', 3);
        $this->retrySleep = config('mikrotik.retry_sleep', 100);
    }

    /**
     * Make an HTTP request to the MikroTik REST API.
     */
    private function request(string $method, string $endpoint, array $data = []): array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($this->timeout)
                ->retry($this->retryTimes, $this->retrySleep)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->$method("{$this->baseUrl}{$endpoint}", $data);

            if ($response->failed()) {
                Log::warning('MikroTik API request failed', [
                    'router_id' => $this->router->id,
                    'router_name' => $this->router->name,
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                $response->throw();
            }

            return $response->json() ?? [];
        } catch (ConnectionException $e) {
            Log::error('MikroTik connection failed', [
                'router_id' => $this->router->id,
                'router_name' => $this->router->name,
                'error' => $e->getMessage(),
            ]);

            throw new MikroTikConnectionException(
                "Failed to connect to router {$this->router->name}: {$e->getMessage()}",
                previous: $e
            );
        } catch (RequestException $e) {
            Log::error('MikroTik request exception', [
                'router_id' => $this->router->id,
                'router_name' => $this->router->name,
                'error' => $e->getMessage(),
            ]);

            throw new MikroTikRequestException(
                "MikroTik API error on router {$this->router->name}: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Test connectivity to the router.
     */
    public function testConnection(): bool
    {
        try {
            $this->request('get', 'system/resource');

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create a hotspot user on the router.
     */
    public function createHotspotUser(string $macAddress, string $profile, ?string $server = 'all'): bool
    {
        $username = $this->macToUsername($macAddress);

        $data = [
            'name' => $username,
            'password' => $username,
            'mac-address' => $macAddress,
            'profile' => $profile,
            'server' => $server ?? 'all',
            'comment' => "BukuTu - {$macAddress}",
        ];

        try {
            $this->request('put', 'ip/hotspot/user', $data);

            Log::info('MikroTik hotspot user created', [
                'router_id' => $this->router->id,
                'mac' => $macAddress,
                'profile' => $profile,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to create MikroTik hotspot user', [
                'router_id' => $this->router->id,
                'mac' => $macAddress,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Enable a hotspot user.
     */
    public function enableHotspotUser(string $macAddress): bool
    {
        $user = $this->getHotspotUser($macAddress);

        if (! $user || ! isset($user['.id'])) {
            return false;
        }

        try {
            $this->request('patch', "ip/hotspot/user/{$user['.id']}", [
                'disabled' => 'no',
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to enable MikroTik hotspot user', [
                'router_id' => $this->router->id,
                'mac' => $macAddress,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Disable a hotspot user.
     */
    public function disableHotspotUser(string $macAddress): bool
    {
        $user = $this->getHotspotUser($macAddress);

        if (! $user || ! isset($user['.id'])) {
            return false;
        }

        try {
            $this->request('patch', "ip/hotspot/user/{$user['.id']}", [
                'disabled' => 'yes',
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to disable MikroTik hotspot user', [
                'router_id' => $this->router->id,
                'mac' => $macAddress,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Remove a hotspot user.
     */
    public function removeHotspotUser(string $macAddress): bool
    {
        $user = $this->getHotspotUser($macAddress);

        if (! $user || ! isset($user['.id'])) {
            return false;
        }

        try {
            $this->request('delete', "ip/hotspot/user/{$user['.id']}");

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to remove MikroTik hotspot user', [
                'router_id' => $this->router->id,
                'mac' => $macAddress,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get a hotspot user by MAC address.
     */
    public function getHotspotUser(string $macAddress): ?array
    {
        try {
            $result = $this->request('get', "ip/hotspot/user?name={$this->macToUsername($macAddress)}");

            if (is_array($result) && ! empty($result)) {
                // MikroTik REST API returns an array of results
                return $result[0] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get all active hotspot users.
     */
    public function getActiveUsers(): array
    {
        try {
            return $this->request('get', 'ip/hotspot/active');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get hotspot host by MAC address.
     */
    public function getHotspotHost(string $macAddress): ?array
    {
        try {
            $result = $this->request('get', "ip/hotspot/host?mac-address={$macAddress}");

            if (is_array($result) && ! empty($result)) {
                return $result[0] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Disconnect/remove an active session by MAC address.
     */
    public function disconnectSession(string $macAddress): bool
    {
        try {
            $activeUsers = $this->getActiveUsers();

            foreach ($activeUsers as $user) {
                if (isset($user['mac-address']) && $user['mac-address'] === $macAddress) {
                    if (isset($user['.id'])) {
                        $this->request('delete', "ip/hotspot/active/{$user['.id']}");

                        return true;
                    }
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to disconnect MikroTik session', [
                'router_id' => $this->router->id,
                'mac' => $macAddress,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Apply bandwidth profile to a hotspot user.
     */
    public function applyProfile(string $macAddress, string $profile): bool
    {
        $user = $this->getHotspotUser($macAddress);

        if (! $user || ! isset($user['.id'])) {
            // Create the user if they don't exist
            return $this->createHotspotUser($macAddress, $profile);
        }

        try {
            $this->request('patch', "ip/hotspot/user/{$user['.id']}", [
                'profile' => $profile,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to apply MikroTik profile', [
                'router_id' => $this->router->id,
                'mac' => $macAddress,
                'profile' => $profile,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get system resources from the router.
     */
    public function getSystemResources(): array
    {
        try {
            return $this->request('get', 'system/resource');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Bypass hotspot for a MAC address (authorize by MAC).
     * This adds the MAC address to the hotspot's bypass/walled-garden list.
     */
    public function authorizeByMac(string $macAddress, string $profile, ?int $timeout = null): bool
    {
        try {
            // First, ensure the hotspot user exists with the right profile
            $this->createHotspotUser($macAddress, $profile);

            // Enable the user
            $this->enableHotspotUser($macAddress);

            // Add to walled garden / bypass list to allow immediate access
            $this->addWalledGardenIp($macAddress);

            Log::info('MikroTik MAC authorized', [
                'router_id' => $this->router->id,
                'mac' => $macAddress,
                'profile' => $profile,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to authorize MAC on MikroTik', [
                'router_id' => $this->router->id,
                'mac' => $macAddress,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Remove a MAC from the hotspot bypass list (deauthorize).
     */
    public function deauthorizeByMac(string $macAddress): bool
    {
        try {
            // Remove from active sessions
            $this->disconnectSession($macAddress);

            // Remove walled garden entry
            $this->removeWalledGardenIp($macAddress);

            // Disable the hotspot user
            $this->disableHotspotUser($macAddress);

            Log::info('MikroTik MAC deauthorized', [
                'router_id' => $this->router->id,
                'mac' => $macAddress,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to deauthorize MAC on MikroTik', [
                'router_id' => $this->router->id,
                'mac' => $macAddress,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Add an IP/host to the walled garden (bypass) list.
     */
    private function addWalledGardenIp(string $ip): bool
    {
        try {
            // Check if entry already exists
            $existing = $this->request('get', "ip/hotspot/walled-garden?dst-host={$ip}");

            if (is_array($existing) && ! empty($existing)) {
                return true; // Already exists
            }

            $this->request('put', 'ip/hotspot/walled-garden', [
                'dst-host' => $ip,
                'action' => 'allow',
                'comment' => "BukuTu - {$ip}",
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Remove an IP/host from the walled garden list.
     */
    private function removeWalledGardenIp(string $ip): bool
    {
        try {
            $existing = $this->request('get', "ip/hotspot/walled-garden?dst-host={$ip}");

            if (is_array($existing) && ! empty($existing) && isset($existing[0]['.id'])) {
                $this->request('delete', "ip/hotspot/walled-garden/{$existing[0]['.id']}");

                return true;
            }

            return true; // Nothing to remove
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Convert a MAC address to a hotspot username.
     */
    private function macToUsername(string $macAddress): string
    {
        return 'bt_' . str_replace(':', '', strtolower($macAddress));
    }
}
