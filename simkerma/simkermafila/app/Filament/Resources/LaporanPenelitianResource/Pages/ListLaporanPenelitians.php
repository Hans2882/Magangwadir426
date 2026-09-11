<?php

namespace App\Filament\Resources\LaporanPenelitianResource\Pages;

use App\Filament\Resources\LaporanPenelitianResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLaporanPenelitians extends ListRecords
{
    protected static string $resource = LaporanPenelitianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
