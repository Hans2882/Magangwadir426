<?php

namespace App\Filament\Resources\MitraAwardPeriodResource\Pages;

use App\Filament\Resources\MitraAwardPeriodResource;
use Filament\Resources\Pages\EditRecord;

class EditMitraAwardPeriod extends EditRecord
{
    protected static string $resource = MitraAwardPeriodResource::class;

    protected function getAllRelationManagers(): array
    {
        return [];
    }
}
