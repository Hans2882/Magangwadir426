<?php

namespace App\Filament\Resources\JurnalIlmiahResource\Pages;

use App\Filament\Resources\JurnalIlmiahResource;
use App\Filament\Resources\Traits\HasKerjasamaReference;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJurnalIlmiah extends EditRecord
{
    use HasKerjasamaReference;

    protected static string $resource = JurnalIlmiahResource::class;

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
