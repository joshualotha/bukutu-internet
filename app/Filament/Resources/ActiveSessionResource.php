<?php

namespace App\Filament\Resources;

use App\Enums\SessionStatus;
use App\Filament\Resources\ActiveSessionResource\Pages;
use App\Models\ActiveSession;
use App\Models\Router;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActiveSessionResource extends Resource
{
    protected static ?string $model = ActiveSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-wifi';

    protected static ?string $navigationGroup = 'Monitoring';

    protected static ?string $recordTitleAttribute = 'mac_address';

    protected static ?string $pluralLabel = 'Active Sessions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Session Details'))
                    ->schema([
                        Forms\Components\TextInput::make('customer.full_name')
                            ->label(__('Customer'))
                            ->disabled(),
                        Forms\Components\TextInput::make('package.name')
                            ->label(__('Package'))
                            ->disabled(),
                        Forms\Components\TextInput::make('router.name')
                            ->label(__('Router'))
                            ->disabled(),
                        Forms\Components\TextInput::make('mac_address')
                            ->label(__('MAC Address'))
                            ->disabled(),
                        Forms\Components\TextInput::make('mikrotik_username')
                            ->label(__('MikroTik Username'))
                            ->disabled(),
                        Forms\Components\TextInput::make('mikrotik_profile')
                            ->label(__('MikroTik Profile'))
                            ->disabled(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make(__('Timing'))
                    ->schema([
                        Forms\Components\DateTimePicker::make('start_time')
                            ->label(__('Start Time'))
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('expiry_time')
                            ->label(__('Expiry Time'))
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('disconnected_at')
                            ->label(__('Disconnected At'))
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->label(__('Status'))
                            ->options(SessionStatus::class)
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.full_name')
                    ->label(__('Customer'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('package.name')
                    ->label(__('Package'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('router.name')
                    ->label(__('Router'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('mac_address')
                    ->label(__('MAC Address'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label(__('Start Time'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expiry_time')
                    ->label(__('Expiry Time'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (SessionStatus $state): string => match ($state) {
                        SessionStatus::ACTIVE => 'success',
                        SessionStatus::EXPIRED => 'danger',
                        SessionStatus::SUSPENDED => 'warning',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('time_remaining')
                    ->label(__('Time Remaining'))
                    ->state(function (ActiveSession $record): string {
                        $seconds = $record->timeRemaining();

                        if ($seconds <= 0) {
                            return '—';
                        }

                        $hours = floor($seconds / 3600);
                        $minutes = floor(($seconds % 3600) / 60);

                        if ($hours > 0) {
                            return "{$hours}h {$minutes}m";
                        }

                        return "{$minutes}m";
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('expiry_time', $direction);
                    }),
            ])
            ->defaultSort('start_time', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(SessionStatus::class),
                Tables\Filters\SelectFilter::make('router_id')
                    ->label(__('Router'))
                    ->options(fn (): array => Router::query()->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label(__('Customer'))
                    ->relationship('customer', 'full_name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('suspend')
                    ->label(__('Suspend'))
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->visible(fn (ActiveSession $record): bool => $record->status === SessionStatus::ACTIVE)
                    ->action(function (ActiveSession $record): void {
                        $record->update(['status' => SessionStatus::SUSPENDED]);

                        Notification::make()
                            ->title(__('Session Suspended'))
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('Suspend Session'))
                    ->modalDescription(__('Are you sure you want to suspend this session? The user will lose internet access until the session is resumed.'))
                    ->modalSubmitActionLabel(__('Suspend')),
                Tables\Actions\Action::make('extend')
                    ->label(__('Extend'))
                    ->icon('heroicon-o-clock')
                    ->color('info')
                    ->visible(fn (ActiveSession $record): bool => in_array($record->status, [SessionStatus::ACTIVE, SessionStatus::SUSPENDED]))
                    ->form([
                        Forms\Components\TextInput::make('minutes')
                            ->label(__('Extend by (minutes)'))
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(60),
                    ])
                    ->action(function (ActiveSession $record, array $data): void {
                        $record->update([
                            'expiry_time' => $record->expiry_time->addMinutes((int) $data['minutes']),
                        ]);

                        Notification::make()
                            ->title(__('Session Extended'))
                            ->body(__('Session extended by :minutes minutes.', ['minutes' => $data['minutes']]))
                            ->success()
                            ->send();
                    })
                    ->modalHeading(__('Extend Session'))
                    ->modalSubmitActionLabel(__('Extend')),
                Tables\Actions\Action::make('disconnect')
                    ->label(__('Disconnect'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (ActiveSession $record): bool => in_array($record->status, [SessionStatus::ACTIVE, SessionStatus::SUSPENDED]))
                    ->action(function (ActiveSession $record): void {
                        $record->update([
                            'status' => SessionStatus::EXPIRED,
                            'disconnected_at' => now(),
                        ]);

                        Notification::make()
                            ->title(__('Session Disconnected'))
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('Disconnect Session'))
                    ->modalDescription(__('Are you sure you want to disconnect this session? The user will immediately lose internet access.'))
                    ->modalSubmitActionLabel(__('Disconnect')),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_disconnect')
                        ->label(__('Disconnect Selected'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $records->each(function (ActiveSession $record): void {
                                if (in_array($record->status, [SessionStatus::ACTIVE, SessionStatus::SUSPENDED])) {
                                    $record->update([
                                        'status' => SessionStatus::EXPIRED,
                                        'disconnected_at' => now(),
                                    ]);
                                }
                            });

                            Notification::make()
                                ->title(__('Sessions Disconnected'))
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading(__('Disconnect Selected Sessions'))
                        ->modalDescription(__('Are you sure you want to disconnect the selected sessions?'))
                        ->modalSubmitActionLabel(__('Disconnect All')),
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
            'index' => Pages\ListActiveSessions::route('/'),
            'view' => Pages\ViewActiveSession::route('/{record}'),
        ];
    }
}
