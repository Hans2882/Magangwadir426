<?php

namespace App\Filament\Resources\MitraAwardPeriodResource\Pages;

use App\Filament\Resources\MitraAwardPeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMitraAwardPeriods extends ListRecords
{
    protected static string $resource = MitraAwardPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Buat Periode')->icon('heroicon-o-plus')];
    }
}
