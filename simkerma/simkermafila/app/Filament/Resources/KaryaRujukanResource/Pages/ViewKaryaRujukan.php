<?php

namespace App\Filament\Resources\KaryaRujukanResource\Pages;

use App\Filament\Resources\KaryaRujukanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewKaryaRujukan extends ViewRecord
{
    protected static string $resource = KaryaRujukanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
