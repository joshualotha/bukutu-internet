<?php

namespace App\Filament\Widgets;

use App\Models\ActiveSession;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ActiveSessionsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Active Sessions by Day';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $sessions = ActiveSession::select(
            DB::raw('DATE(start_time) as date'),
            DB::raw('COUNT(*) as total')
        )
            ->where('start_time', '>=', now()->subDays(30))
            ->groupBy(DB::raw('DATE(start_time)'))
            ->orderBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Sessions Started',
                    'data' => $sessions->pluck('total'),
                    'backgroundColor' => '#10b981',
                    'borderColor' => '#10b981',
                ],
            ],
            'labels' => $sessions->pluck('date'),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
