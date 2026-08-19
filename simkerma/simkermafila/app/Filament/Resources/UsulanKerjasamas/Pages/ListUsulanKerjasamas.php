<?php

namespace App\Filament\Resources\UsulanKerjasamas\Pages;

use App\Filament\Resources\UsulanKerjasamas\UsulanKerjasamaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsulanKerjasamas extends ListRecords
{
    protected static string $resource = UsulanKerjasamaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
