<?php

namespace App\Filament\Resources\LaporanPenelitianResource\Pages;

use App\Filament\Resources\LaporanPenelitianResource;
use App\Filament\Resources\Traits\HasKerjasamaReference;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLaporanPenelitian extends CreateRecord
{
    use HasKerjasamaReference;

    protected static string $resource = LaporanPenelitianResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->setMitraFromKerjasamaReference($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
