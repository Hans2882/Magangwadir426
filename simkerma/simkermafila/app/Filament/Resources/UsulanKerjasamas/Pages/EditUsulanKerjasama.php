<?php

namespace App\Filament\Resources\UsulanKerjasamas\Pages;

use App\Filament\Resources\UsulanKerjasamas\UsulanKerjasamaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUsulanKerjasama extends EditRecord
{
    protected static string $resource = UsulanKerjasamaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
