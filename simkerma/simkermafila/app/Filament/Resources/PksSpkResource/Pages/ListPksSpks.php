<?php

namespace App\Filament\Resources\PksSpkResource\Pages;

use App\Filament\Resources\PksSpkResource;
use Filament\Resources\Pages\ListRecords;

class ListPksSpks extends ListRecords
{
    protected static string $resource = PksSpkResource::class;

    protected function getHeaderActions(): array
    {
        return parent::getHeaderActions();
    }
}
