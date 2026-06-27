<?php

namespace App\Filament\Resources;

use App\Enums\PaymentStatus;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('order.order_reference')
                    ->label(__('Order Reference'))
                    ->disabled(),
                Forms\Components\TextInput::make('amount')
                    ->label(__('Amount'))
                    ->numeric()
                    ->disabled(),
                Forms\Components\TextInput::make('currency')
                    ->label(__('Currency'))
                    ->disabled(),
                Forms\Components\TextInput::make('provider')
                    ->label(__('Provider'))
                    ->disabled(),
                Forms\Components\TextInput::make('provider_reference')
                    ->label(__('Provider Reference'))
                    ->disabled(),
                Forms\Components\TextInput::make('provider_tracking_id')
                    ->label(__('Provider Tracking ID'))
                    ->disabled(),
                Forms\Components\TextInput::make('payment_method')
                    ->label(__('Payment Method'))
                    ->disabled(),
                Forms\Components\TextInput::make('phone_number')
                    ->label(__('Phone Number'))
                    ->disabled(),
                Forms\Components\Select::make('status')
                    ->label(__('Status'))
                    ->options(PaymentStatus::class)
                    ->disabled(),
                Forms\Components\TextInput::make('confirmation_code')
                    ->label(__('Confirmation Code'))
                    ->disabled(),
                Forms\Components\DateTimePicker::make('payment_time')
                    ->label(__('Payment Time'))
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.order_reference')
                    ->label(__('Order Reference'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->money(fn (Payment $record): string => $record->currency ?? 'TZS')
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency')
                    ->label(__('Currency'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('provider')
                    ->label(__('Provider'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label(__('Payment Method'))
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
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Date'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(PaymentStatus::class),
                Tables\Filters\SelectFilter::make('provider')
                    ->label(__('Provider'))
                    ->options(fn (): array => Payment::query()
                        ->distinct()
                        ->whereNotNull('provider')
                        ->pluck('provider', 'provider')
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
                Tables\Actions\Action::make('view_payload')
                    ->label(__('View Payload'))
                    ->icon('heroicon-o-code-bracket')
                    ->modalHeading(fn (Payment $record): string => __('Response Payload #:id', ['id' => $record->id]))
                    ->modalContent(fn (Payment $record) => view('filament.modals.json-payload', [
                        'label' => __('Response Payload'),
                        'data' => $record->response_payload,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('Close')),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\ExportBulkAction::make()
                        ->label(__('Export Selected')),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_all')
                    ->label(__('Export All'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () {
                        // Placeholder for export action; can be wired to a Laravel Excel export
                    }),
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
            'index' => Pages\ListPayments::route('/'),
            'view' => Pages\ViewPayment::route('/{record}'),
        ];
    }
}
