<?php

namespace App\Filament\Resources\LoiResource\Pages;

use App\Filament\Resources\LoiResource;
use Filament\Resources\Pages\EditRecord;

class EditLoi extends EditRecord
{
    protected static string $resource = LoiResource::class;
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
