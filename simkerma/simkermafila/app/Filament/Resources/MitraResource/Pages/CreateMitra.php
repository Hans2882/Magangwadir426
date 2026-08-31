<?php

namespace App\Filament\Resources\MitraResource\Pages;

use App\Filament\Resources\MitraResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMitra extends CreateRecord
{
    protected static string $resource = MitraResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
