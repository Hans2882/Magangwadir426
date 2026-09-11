<?php

namespace App\Filament\Resources\JurnalIlmiahResource\Pages;

use App\Filament\Resources\JurnalIlmiahResource;
use App\Filament\Resources\Traits\HasKerjasamaReference;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateJurnalIlmiah extends CreateRecord
{
    use HasKerjasamaReference;

    protected static string $resource = JurnalIlmiahResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->setMitraFromKerjasamaReference($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
