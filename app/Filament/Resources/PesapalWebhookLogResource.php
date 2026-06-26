<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PesapalWebhookLogResource\Pages;
use App\Models\PesapalWebhookLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PesapalWebhookLogResource extends Resource
{
    protected static ?string $model = PesapalWebhookLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $recordTitleAttribute = 'ipn_type';

    protected static ?string $pluralLabel = 'Webhook Logs';

    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('ipn_type')
                    ->label(__('IPN Type'))
                    ->disabled(),
                Forms\Components\Toggle::make('processed')
                    ->label(__('Processed'))
                    ->disabled(),
                Forms\Components\Textarea::make('error_message')
                    ->label(__('Error Message'))
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('payload')
                    ->label(__('Payload'))
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('created_at')
                    ->label(__('Received At'))
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
                Tables\Columns\TextColumn::make('ipn_type')
                    ->label(__('IPN Type'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\IconColumn::make('processed')
                    ->label(__('Processed'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('error_message')
                    ->label(__('Error'))
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Received At'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('processed')
                    ->label(__('Processed')),
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
                    ->modalHeading(fn (PesapalWebhookLog $record): string => __('Webhook Payload #:id', ['id' => $record->id]))
                    ->modalContent(fn (PesapalWebhookLog $record) => view('filament.modals.json-payload', [
                        'label' => __('Webhook Payload'),
                        'data' => $record->payload,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('Close')),
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
            'index' => Pages\ListPesapalWebhookLogs::route('/'),
            'view' => Pages\ViewPesapalWebhookLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
