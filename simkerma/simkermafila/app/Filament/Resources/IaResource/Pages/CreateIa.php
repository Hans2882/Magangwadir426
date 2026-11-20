<?php

namespace App\Filament\Resources\IaResource\Pages;

use App\Filament\Resources\IaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIa extends CreateRecord
{
    protected static string $resource = IaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = \Illuminate\Support\Facades\Auth::id();
        $data['jenis_dokumen_id'] = 4;
        $data['status_workflow'] = 'Selesai';

        if (!empty($data['tanggal_awal']) && empty($data['tanggal_akhir'])) {
            try {
                $raw = trim((string) $data['tanggal_awal']);
                if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})$/', $raw, $m)) {
                    $c = \Carbon\Carbon::createFromDate((int)$m[3], (int)$m[2], (int)$m[1]);
                } elseif (preg_match('/^(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})$/', $raw, $m)) {
                    $c = \Carbon\Carbon::createFromDate((int)$m[1], (int)$m[2], (int)$m[3]);
                } else {
                    $c = \Carbon\Carbon::parse($raw);
                }
                $data['tanggal_akhir'] = $c->addYear()->format('Y-m-d');
            } catch (\Throwable $e) {}
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user && $user->isDosen()) {
            if ($this->record->prodis()->count() === 0 && $userProdiId = $user->getProdiId()) {
                $this->record->prodis()->attach($userProdiId);
            }
            if ($this->record->jurusans()->count() === 0 && $userJurusanId = $user->getJurusanId()) {
                $this->record->jurusans()->attach($userJurusanId);
            }
        }
    }

}
