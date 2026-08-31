<?php

namespace App\Filament\Resources\KuisionerKepuasanResource\Pages;

use App\Filament\Resources\KuisionerKepuasanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKuisionerKepuasans extends ListRecords
{
    protected static string $resource = KuisionerKepuasanResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
