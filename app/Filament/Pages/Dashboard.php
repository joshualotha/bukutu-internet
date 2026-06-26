<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ActiveSessionsChartWidget;
use App\Filament\Widgets\FailedPaymentsWidget;
use App\Filament\Widgets\PopularPackagesChartWidget;
use App\Filament\Widgets\RecentOrdersWidget;
use App\Filament\Widgets\RevenueChartWidget;
use App\Filament\Widgets\RouterStatusWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            RevenueChartWidget::class,
            ActiveSessionsChartWidget::class,
            PopularPackagesChartWidget::class,
            RecentOrdersWidget::class,
            RouterStatusWidget::class,
            FailedPaymentsWidget::class,
        ];
    }
}
