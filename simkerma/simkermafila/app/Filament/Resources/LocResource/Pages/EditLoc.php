<?php

namespace App\Filament\Resources\LocResource\Pages;

use App\Filament\Resources\LocResource;
use Filament\Resources\Pages\EditRecord;

class EditLoc extends EditRecord
{
    protected static string $resource = LocResource::class;
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
