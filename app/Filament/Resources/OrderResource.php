<?php

namespace App\Filament\Resources;

use App\Enums\PaymentStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?string $recordTitleAttribute = 'order_reference';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('order_reference')
                    ->label(__('Order Reference'))
                    ->disabled(),
                Forms\Components\Select::make('customer_id')
                    ->label(__('Customer'))
                    ->relationship('customer', 'full_name')
                    ->searchable()
                    ->preload()
                    ->disabled(),
                Forms\Components\Select::make('package_id')
                    ->label(__('Package'))
                    ->relationship('package', 'name')
                    ->disabled(),
                Forms\Components\TextInput::make('amount')
                    ->label(__('Amount'))
                    ->numeric()
                    ->disabled(),
                Forms\Components\Select::make('status')
                    ->label(__('Status'))
                    ->options(PaymentStatus::class)
                    ->disabled(),
                Forms\Components\TextInput::make('payment_method')
                    ->label(__('Payment Method'))
                    ->disabled(),
                Forms\Components\TextInput::make('pesapal_tracking_id')
                    ->label(__('Pesapal Tracking ID'))
                    ->disabled(),
                Forms\Components\TextInput::make('pesapal_merchant_ref')
                    ->label(__('Pesapal Merchant Ref'))
                    ->disabled(),
                Forms\Components\TextInput::make('transaction_reference')
                    ->label(__('Transaction Reference'))
                    ->disabled(),
                Forms\Components\DateTimePicker::make('paid_at')
                    ->label(__('Paid At'))
                    ->disabled(),
                Forms\Components\DateTimePicker::make('expired_at')
                    ->label(__('Expired At'))
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_reference')
                    ->label(__('Reference'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('customer.full_name')
                    ->label(__('Customer'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('package.name')
                    ->label(__('Package'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->money(fn (): string => config('pesapal.currency', 'TZS'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (PaymentStatus $state): string => match ($state) {
                        PaymentStatus::PAID => 'success',
                        PaymentStatus::PENDING => 'warning',
                        PaymentStatus::FAILED => 'danger',
                        PaymentStatus::EXPIRED => 'gray',
                        PaymentStatus::REFUNDED => 'info',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label(__('Payment Method'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(PaymentStatus::class),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label(__('Payment Method'))
                    ->options(fn (): array => Order::query()
                        ->distinct()
                        ->whereNotNull('payment_method')
                        ->pluck('payment_method', 'payment_method')
                        ->toArray()),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label(__('From')),
                        Forms\Components\DatePicker::make('created_until')
                            ->label(__('Until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_payment')
                    ->label(__('View Payment'))
                    ->icon('heroicon-o-currency-dollar')
                    ->modalHeading(fn (Order $record): string => __('Payment for :ref', ['ref' => $record->order_reference]))
                    ->modalContent(function (Order $record) {
                        $payment = $record->payments()->latest()->first();

                        if (! $payment) {
                            return view('filament.modals.no-payment-found');
                        }

                        return view('filament.modals.payment-details', [
                            'payment' => $payment,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('Close')),
                Tables\Actions\Action::make('view_customer')
                    ->label(__('View Customer'))
                    ->icon('heroicon-o-user')
                    ->url(fn (Order $record): string => CustomerResource::getUrl('view', ['record' => $record->customer_id]))
                    ->openUrlInNewTab(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
