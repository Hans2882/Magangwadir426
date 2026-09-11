<?php

namespace App\Filament\Resources\LaporanPenelitianResource\Pages;

use App\Filament\Resources\LaporanPenelitianResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLaporanPenelitian extends ViewRecord
{
    protected static string $resource = LaporanPenelitianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
