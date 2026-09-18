<?php

namespace App\Filament\Resources\PermintaanKerjasamas\Pages;

use App\Filament\Resources\PermintaanKerjasamas\PermintaanKerjasamaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPermintaanKerjasamas extends ListRecords
{
    protected static string $resource = PermintaanKerjasamaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
