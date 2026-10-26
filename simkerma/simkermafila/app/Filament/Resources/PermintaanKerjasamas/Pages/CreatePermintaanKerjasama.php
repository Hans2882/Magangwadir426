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
        $prodiId = $pengusul?->getProdiId();

        $recipients = collect();

        // 1. Koordinator Magang: HANYA yang prodinya sama persis dengan program studi mahasiswa pengusul
        if ($prodiId) {
            $koorProdi = User::query()
                ->whereHas('userPrivilege.privilege', function ($q) {
                    $q->where('nama', 'like', '%Koor%');
                })
                ->whereHas('userProgramStudi', function ($q) use ($prodiId) {
                    $q->where('program_studi_id', $prodiId);
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
            $prodiName = $pengusul?->userProgramStudi?->programStudi?->nama_resmi 
                ?? $pengusul?->userProgramStudi?->programStudi?->nama_prodi;
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
