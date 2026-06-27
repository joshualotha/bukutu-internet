<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackageResource\Pages;
use App\Models\Package;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Package Details'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label(__('Description'))
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('price')
                            ->label(__('Price'))
                            ->required()
                            ->numeric()
                            ->prefix(fn (): string => config('pesapal.currency', 'TZS'))
                            ->minValue(0),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->label(__('Duration (minutes)'))
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->helperText(__('How long the package lasts in minutes')),
                    ])
                    ->columns(2),
                Forms\Components\Section::make(__('Speed & Profile'))
                    ->schema([
                        Forms\Components\TextInput::make('upload_speed')
                            ->label(__('Upload Speed'))
                            ->required()
                            ->maxLength(50)
                            ->placeholder('5M'),
                        Forms\Components\TextInput::make('download_speed')
                            ->label(__('Download Speed'))
                            ->required()
                            ->maxLength(50)
                            ->placeholder('10M'),
                        Forms\Components\TextInput::make('mikrotik_profile')
                            ->label(__('MikroTik Profile'))
                            ->required()
                            ->maxLength(255)
                            ->helperText(__('The hotspot user profile to apply on MikroTik')),
                    ])
                    ->columns(3),
                Forms\Components\Section::make(__('Status'))
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('Active'))
                            ->default(true),
                        Forms\Components\TextInput::make('sort_order')
                            ->label(__('Sort Order'))
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
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
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label(__('Price'))
                    ->money(fn (): string => config('pesapal.currency', 'TZS'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label(__('Duration'))
                    ->formatStateUsing(fn (int $state): string => trans_choice('{1} :count minute|[2,*] :count minutes', $state, ['count' => $state]))
                    ->sortable(),
                Tables\Columns\TextColumn::make('upload_speed')
                    ->label(__('Upload'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('download_speed')
                    ->label(__('Download'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('Sort Order'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Active')),
            ])
            ->actions([
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (Package $record): string => $record->is_active ? __('Deactivate') : __('Activate'))
                    ->icon(fn (Package $record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (Package $record): string => $record->is_active ? 'danger' : 'success')
                    ->action(function (Package $record): void {
                        $record->update(['is_active' => ! $record->is_active]);
                    })
                    ->requiresConfirmation(),
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
            'index' => Pages\ListPackages::route('/'),
            'create' => Pages\CreatePackage::route('/create'),
            'edit' => Pages\EditPackage::route('/{record}/edit'),
        ];
    }
}
