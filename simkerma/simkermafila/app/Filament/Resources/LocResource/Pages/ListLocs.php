<?php

namespace App\Filament\Resources\LocResource\Pages;

use App\Filament\Resources\LocResource;
use Filament\Resources\Pages\ListRecords;

class ListLocs extends ListRecords
{
    protected static string $resource = LocResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
