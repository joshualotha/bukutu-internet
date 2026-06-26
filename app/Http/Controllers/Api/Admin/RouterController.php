<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Services\RouterService;
use Illuminate\Http\Request;

class RouterController extends Controller
{
    public function __construct(
        private readonly RouterService $routerService,
    ) {}

    public function test(Router $router)
    {
        $result = $this->routerService->testRouter($router);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Router is online' : 'Router is offline',
            'data' => [
                'connection_status' => $router->fresh()->connection_status,
                'last_seen_at' => $router->fresh()->last_seen_at,
            ],
        ]);
    }

    public function sync(Router $router)
    {
        // Sync active sessions from router
        $activeUsers = $this->routerService->getActiveUsers($router);

        return response()->json([
            'success' => true,
            'message' => 'Router synced successfully',
            'data' => [
                'active_users_count' => count($activeUsers),
                'active_users' => $activeUsers,
            ],
        ]);
    }
}
