<?php

namespace App\Filament\Resources;

use App\Enums\RouterConnectionStatus;
use App\Filament\Resources\RouterResource\Pages;
use App\Models\Router;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class RouterResource extends Resource
{
    protected static ?string $model = Router::class;

    protected static ?string $navigationIcon = 'heroicon-o-server';

    protected static ?string $navigationGroup = 'Network';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Router Details'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('ip_address')
                            ->label(__('IP Address'))
                            ->required()
                            ->maxLength(45),
                        Forms\Components\TextInput::make('api_port')
                            ->label(__('API Port'))
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(65535)
                            ->default(fn (): int => (int) config('mikrotik.default_port', 8728)),
                        Forms\Components\TextInput::make('username')
                            ->label(__('API Username'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->label(__('API Password'))
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? encrypt($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create'),
                        Forms\Components\TextInput::make('location')
                            ->label(__('Location'))
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Forms\Components\Section::make(__('Status & Notes'))
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('Active'))
                            ->default(true),
                        Forms\Components\Textarea::make('notes')
                            ->label(__('Notes'))
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label(__('IP Address'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('connection_status')
                    ->label(__('Connection'))
                    ->badge()
                    ->color(fn (RouterConnectionStatus $state): string => match ($state) {
                        RouterConnectionStatus::ONLINE => 'success',
                        RouterConnectionStatus::OFFLINE => 'danger',
                        RouterConnectionStatus::UNKNOWN => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_seen_at')
                    ->label(__('Last Seen'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Active')),
                Tables\Filters\SelectFilter::make('connection_status')
                    ->label(__('Connection Status'))
                    ->options(RouterConnectionStatus::class),
            ])
            ->actions([
                Tables\Actions\Action::make('test_connection')
                    ->label(__('Test Connection'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(function (Router $record): void {
                        try {
                            // Placeholder for RouterService::testConnection($record)
                            $connected = false; // Will be replaced with actual test

                            if ($connected) {
                                $record->update([
                                    'connection_status' => RouterConnectionStatus::ONLINE,
                                    'last_seen_at' => now(),
                                ]);

                                Notification::make()
                                    ->title(__('Connection Successful'))
                                    ->body(__('Successfully connected to :name', ['name' => $record->name]))
                                    ->success()
                                    ->send();
                            } else {
                                $record->update([
                                    'connection_status' => RouterConnectionStatus::OFFLINE,
                                ]);

                                Notification::make()
                                    ->title(__('Connection Failed'))
                                    ->body(__('Could not connect to :name. Please check the router settings.', ['name' => $record->name]))
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Throwable $e) {
                            Log::error('Router connection test failed', [
                                'router_id' => $record->id,
                                'error' => $e->getMessage(),
                            ]);

                            $record->update([
                                'connection_status' => RouterConnectionStatus::OFFLINE,
                            ]);

                            Notification::make()
                                ->title(__('Connection Error'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('view_sessions')
                    ->label(__('View Sessions'))
                    ->icon('heroicon-o-wifi')
                    ->url(fn (Router $record): string => ActiveSessionResource::getUrl('index', [
                        'tableFilters' => [
                            'router_id' => ['value' => $record->id],
                        ],
                    ]))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListRouters::route('/'),
            'create' => Pages\CreateRouter::route('/create'),
            'edit' => Pages\EditRouter::route('/{record}/edit'),
        ];
    }
}
