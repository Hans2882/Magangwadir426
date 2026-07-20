<?php

namespace App\Filament\Resources\PrivilegeResource\Pages;

use App\Filament\Resources\PrivilegeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPrivileges extends ListRecords
{
    protected static string $resource = PrivilegeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
