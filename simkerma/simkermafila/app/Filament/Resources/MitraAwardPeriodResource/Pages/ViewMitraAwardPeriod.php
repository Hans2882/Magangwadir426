<?php

namespace App\Filament\Resources\MitraAwardPeriodResource\Pages;

use App\Filament\Resources\MitraAwardPeriodResource;
use App\Models\MitraAwardPeriod;
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
                ->record(fn (): MitraAwardPeriod => $this->getRecord())
                ->label('Edit Bobot')
                ->icon('heroicon-o-adjustments-horizontal'),

            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
