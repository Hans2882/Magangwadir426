<?php

namespace App\Filament\Resources\MouResource\Pages;

use App\Filament\Resources\MouResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditMou extends EditRecord
{
    protected static string $resource = MouResource::class;

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
