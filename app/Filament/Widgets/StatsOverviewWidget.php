<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Enums\SessionStatus;
use App\Models\ActiveSession;
use App\Models\Customer;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Customers', Customer::count())
                ->description('Registered hotspot users')
                ->descriptionIcon('heroicon-o-users')
                ->color('info'),

            Stat::make('Active Sessions', ActiveSession::where('status', SessionStatus::ACTIVE)->count())
                ->description('Currently online')
                ->descriptionIcon('heroicon-o-wifi')
                ->color('success'),

            Stat::make('Revenue Today', number_format(Order::where('status', PaymentStatus::PAID)
                ->whereDate('paid_at', today())
                ->sum('amount'), 0) . ' UGX')
                ->description('Sales today')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),

            Stat::make('Revenue This Month', number_format(Order::where('status', PaymentStatus::PAID)
                ->whereYear('paid_at', now()->year)
                ->whereMonth('paid_at', now()->month)
                ->sum('amount'), 0) . ' UGX')
                ->description('Monthly revenue')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('primary'),

            Stat::make('Pending Payments', Order::where('status', PaymentStatus::PENDING)->count())
                ->description('Awaiting confirmation')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Expired Today', ActiveSession::where('status', SessionStatus::EXPIRED)
                ->whereDate('updated_at', today())
                ->count())
                ->description('Sessions expired')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }
}
