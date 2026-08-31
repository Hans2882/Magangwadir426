<?php

namespace App\Filament\Resources\MasterKotas\Pages;

use App\Filament\Resources\MasterKotas\MasterKotaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMasterKotas extends ManageRecords
{
    protected static string $resource = MasterKotaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
