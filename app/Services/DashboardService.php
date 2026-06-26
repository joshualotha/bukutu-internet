<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\ActiveSession;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Router;
use App\Enums\SessionStatus;

class DashboardService
{
    public function __construct(
        private readonly ReportService $reportService,
    ) {}

    /**
     * Get aggregate metrics for the admin dashboard.
     */
    public function getMetrics(): array
    {
        return [
            'total_customers' => Customer::count(),
            'active_sessions' => ActiveSession::where('status', SessionStatus::ACTIVE)->count(),
            'revenue_today' => $this->reportService->dailyRevenue(now()),
            'revenue_this_month' => $this->reportService->monthlyRevenue(now()->month, now()->year),
            'pending_payments' => Order::where('status', PaymentStatus::PENDING)->count(),
            'total_routers' => Router::where('is_active', true)->count(),
            'online_routers' => Router::where('connection_status', 'online')->count(),
            'offline_routers' => Router::where('connection_status', 'offline')->count(),
            'expired_sessions_today' => ActiveSession::where('status', SessionStatus::EXPIRED)
                ->whereDate('updated_at', now()->toDateString())
                ->count(),
        ];
    }

    /**
     * Get chart data for the dashboard.
     */
    public function getCharts(): array
    {
        $revenueLast30Days = $this->reportService->revenueByDay(30);
        $activeUsersByDay = $this->reportService->activeUsersByDay('30_days');
        $popularPackages = $this->reportService->popularPackages('30_days');

        return [
            'revenue_chart' => [
                'labels' => $revenueLast30Days->pluck('date'),
                'data' => $revenueLast30Days->pluck('revenue'),
            ],
            'active_users_chart' => [
                'labels' => $activeUsersByDay->pluck('date'),
                'data' => $activeUsersByDay->pluck('active_users'),
            ],
            'popular_packages' => [
                'labels' => $popularPackages->pluck('name'),
                'data' => $popularPackages->pluck('total_orders'),
            ],
        ];
    }

    /**
     * Get recent orders for the dashboard widget.
     */
    public function getRecentOrders(int $limit = 10)
    {
        return Order::with(['customer', 'package'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get router status summary.
     */
    public function getRouterStatus()
    {
        return Router::where('is_active', true)
            ->select(['id', 'name', 'ip_address', 'connection_status', 'last_seen_at'])
            ->get();
    }

    /**
     * Get failed payments summary.
     */
    public function getFailedPayments(int $limit = 10)
    {
        return $this->reportService->failedPayments('30_days')->take($limit);
    }
}
