<?php

namespace App\Filament\Resources\KaryaRujukanResource\Pages;

use App\Filament\Resources\KaryaRujukanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKaryaRujukans extends ListRecords
{
    protected static string $resource = KaryaRujukanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
