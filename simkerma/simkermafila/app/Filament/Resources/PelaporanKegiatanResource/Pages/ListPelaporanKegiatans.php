<?php

namespace App\Filament\Resources\PelaporanKegiatanResource\Pages;

use App\Filament\Resources\PelaporanKegiatanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPelaporanKegiatans extends ListRecords
{
    protected static string $resource = PelaporanKegiatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

