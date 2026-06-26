<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function metrics()
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getMetrics(),
        ]);
    }

    public function charts()
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getCharts(),
        ]);
    }
}
