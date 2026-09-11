<?php

namespace App\Filament\Resources\LaporanPenelitianResource\Pages;

use App\Filament\Resources\LaporanPenelitianResource;
use App\Filament\Resources\Traits\HasKerjasamaReference;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLaporanPenelitian extends EditRecord
{
    use HasKerjasamaReference;

    protected static string $resource = LaporanPenelitianResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->setMitraFromKerjasamaReference($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
