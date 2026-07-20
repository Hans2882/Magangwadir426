<?php

namespace App\Filament\Resources\PksSpkResource\Pages;

use App\Filament\Resources\PksSpkResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePksSpk extends CreateRecord
{
    protected static string $resource = PksSpkResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
