<?php

namespace App\Filament\Resources\PermintaanKerjasamas\Pages;

use App\Filament\Resources\PermintaanKerjasamas\PermintaanKerjasamaResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePermintaanKerjasama extends CreateRecord
{
    protected static string $resource = PermintaanKerjasamaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = \Illuminate\Support\Facades\Auth::id();
        $data['nama_pengusul'] = \Illuminate\Support\Facades\Auth::user()?->name;
        return $data;
    }
}
