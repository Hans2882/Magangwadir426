<?php

namespace App\Filament\Resources\MoaResource\Pages;

use App\Filament\Resources\MoaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMoa extends EditRecord
{
    protected static string $resource = MoaResource::class;

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
