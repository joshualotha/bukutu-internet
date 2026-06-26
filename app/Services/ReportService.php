<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\ActiveSession;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Package;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Calculate daily revenue for a given date.
     */
    public function dailyRevenue(Carbon $date): float
    {
        return (float) Order::where('status', PaymentStatus::PAID)
            ->whereDate('paid_at', $date->toDateString())
            ->sum('amount');
    }

    /**
     * Calculate monthly revenue for a given month/year.
     */
    public function monthlyRevenue(int $month, int $year): float
    {
        return (float) Order::where('status', PaymentStatus::PAID)
            ->whereYear('paid_at', $year)
            ->whereMonth('paid_at', $month)
            ->sum('amount');
    }

    /**
     * Get the most popular packages for a given period.
     */
    public function popularPackages(string $period = '30_days'): Collection
    {
        $date = match ($period) {
            '7_days' => now()->subDays(7),
            '30_days' => now()->subDays(30),
            '90_days' => now()->subDays(90),
            'year' => now()->subYear(),
            default => now()->subDays(30),
        };

        return Package::select('packages.*', DB::raw('COUNT(orders.id) as total_orders'))
            ->leftJoin('orders', 'packages.id', '=', 'orders.package_id')
            ->where('orders.status', PaymentStatus::PAID)
            ->where('orders.paid_at', '>=', $date)
            ->groupBy('packages.id')
            ->orderByDesc('total_orders')
            ->limit(10)
            ->get();
    }

    /**
     * Calculate customer retention metrics.
     */
    public function customerRetention(): array
    {
        $totalCustomers = Customer::count();
        $returningCustomers = Customer::has('orders', '>=', 2)->count();
        $oneTimeCustomers = Customer::has('orders', '=', 1)->count();

        $retentionRate = $totalCustomers > 0
            ? round(($returningCustomers / $totalCustomers) * 100, 2)
            : 0;

        return [
            'total_customers' => $totalCustomers,
            'returning_customers' => $returningCustomers,
            'one_time_customers' => $oneTimeCustomers,
            'retention_rate' => $retentionRate,
        ];
    }

    /**
     * Get active users by day for a given period.
     */
    public function activeUsersByDay(string $period = '30_days'): Collection
    {
        $days = match ($period) {
            '7_days' => 7,
            '30_days' => 30,
            '90_days' => 90,
            'year' => 365,
            default => 30,
        };

        $startDate = now()->subDays($days);

        return ActiveSession::select(
            DB::raw('DATE(start_time) as date'),
            DB::raw('COUNT(DISTINCT customer_id) as active_users')
        )
            ->where('start_time', '>=', $startDate)
            ->groupBy(DB::raw('DATE(start_time)'))
            ->orderBy('date')
            ->get();
    }

    /**
     * Get failed payments for a given period.
     */
    public function failedPayments(string $period = '30_days'): Collection
    {
        $date = match ($period) {
            '7_days' => now()->subDays(7),
            '30_days' => now()->subDays(30),
            '90_days' => now()->subDays(90),
            default => now()->subDays(30),
        };

        return Payment::whereIn('status', [PaymentStatus::FAILED, PaymentStatus::EXPIRED])
            ->where('created_at', '>=', $date)
            ->with('order.customer')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
    }

    /**
     * Get device usage statistics by platform.
     */
    public function deviceUsage(string $period = '30_days'): Collection
    {
        $date = match ($period) {
            '7_days' => now()->subDays(7),
            '30_days' => now()->subDays(30),
            '90_days' => now()->subDays(90),
            default => now()->subDays(30),
        };

        return Customer::select('device_name', DB::raw('COUNT(*) as count'))
            ->whereNotNull('device_name')
            ->where('created_at', '>=', $date)
            ->groupBy('device_name')
            ->orderByDesc('count')
            ->limit(10)
            ->get();
    }

    /**
     * Get revenue by day for charting.
     */
    public function revenueByDay(int $days = 30): Collection
    {
        $startDate = now()->subDays($days);

        return Order::select(
            DB::raw('DATE(paid_at) as date'),
            DB::raw('SUM(amount) as revenue')
        )
            ->where('status', PaymentStatus::PAID)
            ->where('paid_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE(paid_at)'))
            ->orderBy('date')
            ->get();
    }
}
