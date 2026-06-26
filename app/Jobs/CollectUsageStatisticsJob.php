<?php

namespace App\Jobs;

use App\Models\Router;
use App\Services\RouterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CollectUsageStatisticsJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * Collect usage statistics from all active routers.
     */
    public function handle(RouterService $routerService): void
    {
        $routers = Router::where('is_active', true)->get();

        foreach ($routers as $router) {
            try {
                $activeUsers = $routerService->getActiveUsers($router);

                // Store the count in cache for dashboard reporting
                Cache::put(
                    "router.{$router->id}.active_users_count",
                    count($activeUsers),
                    now()->addHours(2)
                );

                // Store the full list for reference
                Cache::put(
                    "router.{$router->id}.active_users",
                    $activeUsers,
                    now()->addHours(2)
                );
            } catch (\Exception $e) {
                Log::error('Failed to collect usage stats from router', [
                    'router_id' => $router->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('CollectUsageStatisticsJob: Collected stats from all routers');
    }
}
