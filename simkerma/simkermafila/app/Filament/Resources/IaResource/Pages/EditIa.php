<?php

namespace App\Filament\Resources\IaResource\Pages;

use App\Filament\Resources\IaResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditIa extends EditRecord
{
    protected static string $resource = IaResource::class;

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
