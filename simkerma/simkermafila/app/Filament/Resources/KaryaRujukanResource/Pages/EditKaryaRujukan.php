<?php

namespace App\Filament\Resources\KaryaRujukanResource\Pages;

use App\Filament\Resources\KaryaRujukanResource;
use App\Filament\Resources\Traits\HasKerjasamaReference;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKaryaRujukan extends EditRecord
{
    use HasKerjasamaReference;

    protected static string $resource = KaryaRujukanResource::class;

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
