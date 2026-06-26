<?php

namespace App\Services;

use App\Enums\RouterConnectionStatus;
use App\Integrations\MikroTik\MikroTikClient;
use App\Models\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RouterService
{
    /**
     * Get a MikroTik client for a given router.
     */
    public function getClientForRouter(Router $router): MikroTikClient
    {
        return new MikroTikClient($router);
    }

    /**
     * Test connectivity for all active routers and update status.
     */
    public function testAllRouters(): void
    {
        $routers = Router::where('is_active', true)->get();

        foreach ($routers as $router) {
            $this->testRouter($router);
        }
    }

    /**
     * Test connectivity for a single router.
     */
    public function testRouter(Router $router): bool
    {
        try {
            $client = new MikroTikClient($router);
            $result = $client->testConnection();

            $router->update([
                'connection_status' => $result ? RouterConnectionStatus::ONLINE : RouterConnectionStatus::OFFLINE,
                'last_seen_at' => $result ? now() : $router->last_seen_at,
            ]);

            if (! $result) {
                Log::warning('Router connectivity test failed', [
                    'router_id' => $router->id,
                    'router_name' => $router->name,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $router->update([
                'connection_status' => RouterConnectionStatus::OFFLINE,
            ]);

            Log::error('Router test failed with exception', [
                'router_id' => $router->id,
                'router_name' => $router->name,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get all online routers.
     */
    public function getOnlineRouters(): Collection
    {
        return Router::where('is_active', true)
            ->where('connection_status', RouterConnectionStatus::ONLINE)
            ->get();
    }

    /**
     * Get system resources from a router.
     */
    public function getRouterResources(Router $router): array
    {
        try {
            $client = new MikroTikClient($router);

            return $client->getSystemResources();
        } catch (\Exception $e) {
            Log::error('Failed to get router resources', [
                'router_id' => $router->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get active hotspot users from a router.
     */
    public function getActiveUsers(Router $router): array
    {
        try {
            $client = new MikroTikClient($router);

            return $client->getActiveUsers();
        } catch (\Exception $e) {
            Log::error('Failed to get active users from router', [
                'router_id' => $router->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
