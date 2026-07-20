<?php

namespace App\Filament\Resources\PrivilegeResource\Pages;

use App\Filament\Resources\PrivilegeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPrivilege extends EditRecord
{
    protected static string $resource = PrivilegeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
