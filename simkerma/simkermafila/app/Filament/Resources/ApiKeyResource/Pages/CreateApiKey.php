<?php

namespace App\Filament\Resources\ApiKeyResource\Pages;

use App\Filament\Resources\ApiKeyResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateApiKey extends CreateRecord
{
    protected static string $resource = ApiKeyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        $data['key'] = \App\Models\ApiKey::generateKey();

        return $data;
    }

    public function getCreatedNotificationTitle(): ?string
    {
        return 'API Key generated successfully';
    }

    public function getCreatedNotificationMessage(): ?string
    {
        return 'Your new API key has been created. Make sure to save it in a secure place as you won\'t be able to see it again.';
    }
}
