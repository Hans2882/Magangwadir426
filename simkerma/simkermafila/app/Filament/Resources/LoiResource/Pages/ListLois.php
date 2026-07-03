<?php

namespace App\Filament\Resources\LoiResource\Pages;

use App\Filament\Resources\LoiResource;
use Filament\Resources\Pages\ListRecords;

class ListLois extends ListRecords
{
    protected static string $resource = LoiResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
