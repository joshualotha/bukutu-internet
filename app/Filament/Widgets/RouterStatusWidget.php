<?php

namespace App\Filament\Widgets;

use App\Models\Router;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RouterStatusWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected ?string $heading = 'Router Status';

    protected function getStats(): array
    {
        $routers = Router::where('is_active', true)->get();

        if ($routers->isEmpty()) {
            return [
                Stat::make('Routers', 'No routers configured')
                    ->description('Add a router to get started')
                    ->color('gray'),
            ];
        }

        return $routers->map(function (Router $router) {
            $color = match ($router->connection_status) {
                'online' => 'success',
                'offline' => 'danger',
                default => 'gray',
            };

            $icon = match ($router->connection_status) {
                'online' => 'heroicon-o-check-circle',
                'offline' => 'heroicon-o-x-circle',
                default => 'heroicon-o-question-mark-circle',
            };

            return Stat::make($router->name, ucfirst($router->connection_status))
                ->description($router->ip_address . ($router->last_seen_at ? ' | Last seen: ' . $router->last_seen_at->diffForHumans() : ''))
                ->descriptionIcon($icon)
                ->color($color);
        })->toArray();
    }
}
