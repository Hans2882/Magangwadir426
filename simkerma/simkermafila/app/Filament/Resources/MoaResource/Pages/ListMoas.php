<?php

namespace App\Filament\Resources\MoaResource\Pages;

use App\Filament\Resources\MoaResource;
use Filament\Resources\Pages\ListRecords;

class ListMoas extends ListRecords
{
    protected static string $resource = MoaResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
