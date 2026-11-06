<?php

namespace App\Filament\Resources\PelaporanKegiatanResource\Pages;

use App\Filament\Resources\PelaporanKegiatanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPelaporanKegiatan extends EditRecord
{
    protected static string $resource = PelaporanKegiatanResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ((int) ($data['jenis_dokumen_id'] ?? 8) === 9) {
            $data['link_press_release'] = $data['link_dokumen'] ?? null;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // If Press Release, map and normalize link_press_release to link_dokumen
        if ((int) ($data['jenis_dokumen_id'] ?? 8) === 9) {
            $url = trim((string) ($data['link_press_release'] ?? ''));
            if (!empty($url) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }
            $data['link_dokumen'] = !empty($url) ? $url : null;
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

