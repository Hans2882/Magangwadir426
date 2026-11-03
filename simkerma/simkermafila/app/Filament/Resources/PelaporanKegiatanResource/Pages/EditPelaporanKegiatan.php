<?php

namespace App\Filament\Resources\PelaporanKegiatanResource\Pages;

use App\Filament\Resources\PelaporanKegiatanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPelaporanKegiatan extends EditRecord
{
    protected static string $resource = PelaporanKegiatanResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // If Press Release, map link_press_release to link_dokumen
        if ((int) ($data['jenis_dokumen_id'] ?? 8) === 9) {
            $data['link_dokumen'] = $data['link_press_release'] ?? null;
        }
        unset($data['link_press_release']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

