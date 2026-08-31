<?php

namespace App\Filament\Resources\MoaResource\Pages;

use App\Filament\Resources\MoaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMoa extends CreateRecord
{
    protected static string $resource = MoaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
