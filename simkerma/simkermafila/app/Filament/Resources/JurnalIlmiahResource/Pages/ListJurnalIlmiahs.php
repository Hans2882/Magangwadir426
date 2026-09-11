<?php

namespace App\Filament\Resources\JurnalIlmiahResource\Pages;

use App\Filament\Resources\JurnalIlmiahResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJurnalIlmiahs extends ListRecords
{
    protected static string $resource = JurnalIlmiahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
