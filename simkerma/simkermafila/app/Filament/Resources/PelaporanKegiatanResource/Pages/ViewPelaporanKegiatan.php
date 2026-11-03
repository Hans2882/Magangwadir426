<?php

namespace App\Filament\Resources\PelaporanKegiatanResource\Pages;

use App\Filament\Resources\PelaporanKegiatanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPelaporanKegiatan extends ViewRecord
{
    protected static string $resource = PelaporanKegiatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

