<?php

namespace App\Filament\Resources\MasterProvinsis\Pages;

use App\Filament\Resources\MasterProvinsis\MasterProvinsiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMasterProvinsis extends ManageRecords
{
    protected static string $resource = MasterProvinsiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
