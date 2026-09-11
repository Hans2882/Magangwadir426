<?php

namespace App\Filament\Resources\KaryaRujukanResource\Pages;

use App\Filament\Resources\KaryaRujukanResource;
use App\Filament\Resources\Traits\HasKerjasamaReference;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateKaryaRujukan extends CreateRecord
{
    use HasKerjasamaReference;

    protected static string $resource = KaryaRujukanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->setMitraFromKerjasamaReference($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
