<?php

namespace App\Filament\Resources\PelaporanKegiatanResource\Pages;

use App\Filament\Resources\PelaporanKegiatanResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePelaporanKegiatan extends CreateRecord
{
    protected static string $resource = PelaporanKegiatanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $data['user_id'] = $user?->id;
        
        if ($user?->isMahasiswa() || $user?->isDosen()) {
            $data['status_workflow'] = 'Proses';
        }

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

    protected function afterCreate(): void
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user) {
            if ($this->record->prodis()->count() === 0 && $userProdiId = $user->getProdiId()) {
                $this->record->prodis()->attach($userProdiId);
            }
            if ($this->record->jurusans()->count() === 0 && $userJurusanId = $user->getJurusanId()) {
                $this->record->jurusans()->attach($userJurusanId);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

