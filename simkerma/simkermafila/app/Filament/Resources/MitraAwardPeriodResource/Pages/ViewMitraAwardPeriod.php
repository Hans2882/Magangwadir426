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
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
