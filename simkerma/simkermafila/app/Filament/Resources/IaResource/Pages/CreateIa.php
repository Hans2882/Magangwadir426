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
        $user = \Illuminate\Support\Facades\Auth::user();

        // Validasi batasan Dosen: hanya boleh mengunggah IA
        if ($user && $user->isDosen()) {
            $detectedCat = strtoupper(trim((string) ($data['ai_detected_category'] ?? '')));
            if ($detectedCat && !in_array($detectedCat, ['IA', 'IMPLEMENTATION ARRANGEMENT'])) {
                \Filament\Notifications\Notification::make()
                    ->title('Dokumen Tidak Dapat Disimpan')
                    ->body("Berkas yang Anda unggah terdeteksi sebagai dokumen {$detectedCat}. Role Dosen hanya dapat mengunggah dokumen Implementation Arrangement (IA).")
                    ->danger()
                    ->persistent()
                    ->send();
                $this->halt();
            }

            $fileRaw = $data['link_dokumen'] ?? null;
            $fileName = is_array($fileRaw) ? (array_values($fileRaw)[0] ?? '') : $fileRaw;
            if (is_string($fileName) && !empty($fileName)) {
                $lowerName = strtolower(basename($fileName));
                if (preg_match('/\b(mou|nota[-_ ]kesepahaman)\b/i', $lowerName)) {
                    \Filament\Notifications\Notification::make()
                        ->title('Dokumen Tidak Dapat Disimpan')
                        ->body('Berkas yang Anda unggah terindikasi sebagai dokumen MoU. Role Dosen hanya dapat mengunggah dokumen Implementation Arrangement (IA).')
                        ->danger()
                        ->persistent()
                        ->send();
                    $this->halt();
                } elseif (preg_match('/\b(pks|spk|perjanjian[-_ ]kerja[-_ ]sama|perjanjian[-_ ]kerjasama)\b/i', $lowerName)) {
                    \Filament\Notifications\Notification::make()
                        ->title('Dokumen Tidak Dapat Disimpan')
                        ->body('Berkas yang Anda unggah terindikasi sebagai dokumen PKS/SPK. Role Dosen hanya dapat mengunggah dokumen Implementation Arrangement (IA).')
                        ->danger()
                        ->persistent()
                        ->send();
                    $this->halt();
                }
            }
        }

        $data['user_id'] = $user?->id;
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
