<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminActivityLogResource\Pages;
use App\Models\AdminActivityLog;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AdminActivityLogResource extends Resource
{
    protected static ?string $model = AdminActivityLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $recordTitleAttribute = 'action';

    protected static ?string $pluralLabel = 'Activity Logs';

    protected static ?int $navigationSort = 90;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('admin.name')
                    ->label(__('Admin'))
                    ->disabled(),
                Forms\Components\TextInput::make('action')
                    ->label(__('Action'))
                    ->disabled(),
                Forms\Components\TextInput::make('model_type')
                    ->label(__('Model Type'))
                    ->disabled(),
                Forms\Components\TextInput::make('model_id')
                    ->label(__('Model ID'))
                    ->disabled(),
                Forms\Components\KeyValue::make('metadata')
                    ->label(__('Metadata'))
                    ->disabled(),
                Forms\Components\TextInput::make('ip_address')
                    ->label(__('IP Address'))
                    ->disabled(),
                Forms\Components\DateTimePicker::make('created_at')
                    ->label(__('Date'))
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
                Tables\Columns\TextColumn::make('admin.name')
                    ->label(__('Admin'))
                    ->searchable()
                    ->sortable()
                    ->placeholder(__('System')),
                Tables\Columns\TextColumn::make('action')
                    ->label(__('Action'))
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('model_type')
                    ->label(__('Model'))
                    ->sortable()
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—'),
                Tables\Columns\TextColumn::make('model_id')
                    ->label(__('Model ID'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label(__('IP Address'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Date'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label(__('Admin'))
                    ->options(fn (): array => User::query()->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('action')
                    ->label(__('Action'))
                    ->options(fn (): array => AdminActivityLog::query()
                        ->distinct()
                        ->pluck('action', 'action')
                        ->toArray())
                    ->searchable(),
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
            'index' => Pages\ListAdminActivityLogs::route('/'),
            'view' => Pages\ViewAdminActivityLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
