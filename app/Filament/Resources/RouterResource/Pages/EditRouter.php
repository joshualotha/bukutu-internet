<?php

namespace App\Filament\Resources\RouterResource\Pages;

use App\Filament\Resources\RouterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRouter extends EditRecord
{
    protected static string $resource = RouterResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Encrypt password if it was changed (non-empty)
        if (filled($data['password'] ?? null)) {
            $data['password'] = encrypt($data['password']);
        } else {
            // Keep existing encrypted password
            unset($data['password']);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
