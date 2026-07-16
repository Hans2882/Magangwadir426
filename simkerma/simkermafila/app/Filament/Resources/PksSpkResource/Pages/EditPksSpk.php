<?php

namespace App\Filament\Resources\PksSpkResource\Pages;

use App\Filament\Resources\PksSpkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPksSpk extends EditRecord
{
    protected static string $resource = PksSpkResource::class;

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
