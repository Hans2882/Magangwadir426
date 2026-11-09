<?php

namespace App\Filament\Resources\PermintaanKerjasamas\Pages;

use App\Filament\Resources\PermintaanKerjasamas\PermintaanKerjasamaResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
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

    protected function afterCreate(): void
    {
        $record = $this->record;
        $pengusul = $record->pengusul ?? \Illuminate\Support\Facades\Auth::user();
        $pengusulName = $pengusul?->name ?? 'Mahasiswa';
        $prodiIds = $pengusul?->getProdiIds() ?? [];

        $recipients = collect();

        // 1. Koordinator Magang: HANYA yang prodinya mencakup program studi mahasiswa pengusul
        if (!empty($prodiIds)) {
            $koorProdi = User::query()
                ->whereHas('userPrivilege.privilege', function ($q) {
                    $q->where('nama', 'like', '%Koor%');
                })
                ->whereHas('userProgramStudis', function ($q) use ($prodiIds) {
                    $q->whereIn('program_studi_id', $prodiIds);
                })
                ->get();

            $recipients = $recipients->merge($koorProdi);
        }

        // 2. Super Admin / Admin Panel (untuk monitoring seluruh pengajuan)
        $admins = User::query()
            ->whereHas('userPrivilege.privilege', function ($q) {
                $q->where('is_admin_panel', true);
            })
            ->get();

        $recipients = $recipients->merge($admins)->unique('id');

        if ($recipients->isNotEmpty()) {
            $prodiNames = $pengusul?->programStudis?->pluck('nama_prodi')->toArray();
            $prodiName = !empty($prodiNames) 
                ? implode(', ', $prodiNames) 
                : ($pengusul?->userProgramStudi?->programStudi?->nama_resmi ?? $pengusul?->userProgramStudi?->programStudi?->nama_prodi);

            $body = $prodiName
                ? "Mahasiswa {$pengusulName} ({$prodiName}) mengajukan usulan mitra magang: {$record->nama_mitra}."
                : "Mahasiswa {$pengusulName} mengajukan usulan mitra magang: {$record->nama_mitra}.";

            Notification::make()
                ->title('Permintaan Kerjasama Magang Baru')
                ->body($body)
                ->icon('heroicon-o-briefcase')
                ->info()
                ->actions([
                    Action::make('view')
                        ->label('Lihat Daftar')
                        ->url(PermintaanKerjasamaResource::getUrl('index')),
                ])
                ->sendToDatabase($recipients);
        }
    }
}
