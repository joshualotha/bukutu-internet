<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class FailedPaymentsWidget extends BaseWidget
{
    protected static ?int $sort = 7;

    protected static ?string $heading = 'Recent Failed Payments';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Payment::whereIn('status', [PaymentStatus::FAILED, PaymentStatus::EXPIRED])
                    ->with('order.customer')
                    ->orderByDesc('created_at')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('order.order_reference')
                    ->label('Order'),
                TextColumn::make('order.customer.full_name')
                    ->label('Customer')
                    ->default('N/A'),
                TextColumn::make('amount')
                    ->money('TZS'),
                TextColumn::make('payment_method')
                    ->label('Method'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'failed' => 'danger',
                        'expired' => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime(),
            ])
            ->emptyStateHeading('No failed payments')
            ->emptyStateDescription('All payments are processing normally.');
    }
}
