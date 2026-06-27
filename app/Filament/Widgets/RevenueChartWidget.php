<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RevenueChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Revenue (Last 30 Days)';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $revenue = Order::select(
            DB::raw('DATE(paid_at) as date'),
            DB::raw('SUM(amount) as total')
        )
            ->where('status', PaymentStatus::PAID)
            ->where('paid_at', '>=', now()->subDays(30))
            ->groupBy(DB::raw('DATE(paid_at)'))
            ->orderBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (TZS)',
                    'data' => $revenue->pluck('total'),
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#3b82f6',
                ],
            ],
            'labels' => $revenue->pluck('date'),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
