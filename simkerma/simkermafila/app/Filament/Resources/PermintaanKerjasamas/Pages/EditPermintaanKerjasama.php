<?php

namespace App\Filament\Resources\PermintaanKerjasamas\Pages;

use App\Filament\Resources\PermintaanKerjasamas\PermintaanKerjasamaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPermintaanKerjasama extends EditRecord
{
    protected static string $resource = PermintaanKerjasamaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
