<?php

namespace App\Filament\Resources\RouterResource\Pages;

use App\Filament\Resources\RouterResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRouter extends CreateRecord
{
    protected static string $resource = RouterResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Encrypt password before saving
        if (filled($data['password'] ?? null)) {
            $data['password'] = encrypt($data['password']);
        }

        return static::getModel()::create($data);
    }
}
