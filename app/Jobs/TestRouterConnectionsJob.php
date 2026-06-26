<?php

namespace App\Jobs;

use App\Services\RouterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class TestRouterConnectionsJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * Test connectivity for all active routers.
     */
    public function handle(RouterService $routerService): void
    {
        $routerService->testAllRouters();

        Log::info('TestRouterConnectionsJob: Tested all router connections');
    }
}
