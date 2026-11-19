<?php

namespace App\Filament\Resources\MitraAwardPeriodResource\Pages;

use App\Filament\Resources\MitraAwardPeriodResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMitraAwardPeriod extends ViewRecord
{
    protected static string $resource = MitraAwardPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            MitraAwardPeriodResource::configurationAction()
                ->label('Bobot'),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
