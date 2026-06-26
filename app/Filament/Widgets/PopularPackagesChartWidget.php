<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Models\Package;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PopularPackagesChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Most Popular Packages';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $packages = Package::select('packages.name', DB::raw('COUNT(orders.id) as total'))
            ->leftJoin('orders', 'packages.id', '=', 'orders.package_id')
            ->where('orders.status', PaymentStatus::PAID)
            ->where('orders.paid_at', '>=', now()->subDays(30))
            ->groupBy('packages.id', 'packages.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => $packages->pluck('total'),
                    'backgroundColor' => [
                        '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
                        '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#6366f1',
                    ],
                ],
            ],
            'labels' => $packages->pluck('name'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
