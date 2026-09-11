<?php

namespace App\Filament\Resources\PelaporanKaryaTulisIlmiahResource\Pages;

use App\Filament\Resources\PelaporanKaryaTulisIlmiahResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPelaporanKaryaTulisIlmiahs extends ListRecords
{
    protected static string $resource = PelaporanKaryaTulisIlmiahResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}