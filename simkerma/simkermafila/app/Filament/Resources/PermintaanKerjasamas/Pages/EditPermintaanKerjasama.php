<?php

namespace App\Filament\Resources\PermintaanKerjasamas\Pages;

use App\Filament\Resources\PermintaanKerjasamas\PermintaanKerjasamaResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditPermintaanKerjasama extends EditRecord
{
    protected static string $resource = PermintaanKerjasamaResource::class;

    protected ?string $previousStatus = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $this->previousStatus = $this->record->status_permintaan;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = Auth::user();

        // Jika mahasiswa mengedit pengajuan yang sebelumnya Ditolak, kembalikan ke status Proses
        if ($user?->isMahasiswa() && $this->record->status_permintaan === 'Ditolak') {
            $data['status_permintaan'] = 'Proses';
            $data['keterangan_tolak'] = null;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        $user = Auth::user();

        // Jika pengajuan yang ditolak direvisi oleh mahasiswa dan statusnya kembali menjadi Proses
        if ($user?->isMahasiswa() && $this->previousStatus === 'Ditolak') {
            $pengusul = $record->pengusul ?? $user;
            $pengusulName = $pengusul?->name ?? 'Mahasiswa';
            $prodiIds = $pengusul?->getProdiIds() ?? [];

            $recipients = collect();

            // 1. Koordinator Magang sesuai prodi mahasiswa
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

            // 2. Admin Panel
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
                    ? "Mahasiswa {$pengusulName} ({$prodiName}) telah memperbarui data usulan mitra: \"{$record->nama_mitra}\"."
                    : "Mahasiswa {$pengusulName} telah memperbarui data usulan mitra: \"{$record->nama_mitra}\".";

                Notification::make()
                    ->title('Revisi Permintaan Mitra Baru')
                    ->body($body)
                    ->icon('heroicon-o-arrow-path')
                    ->info()
                    ->actions([
                        Action::make('view')
                            ->label('Tinjau Kembali')
                            ->url(PermintaanKerjasamaResource::getUrl('index')),
                    ])
                    ->sendToDatabase($recipients);
            }

            Notification::make()
                ->title('Usulan Berhasil Direvisi')
                ->body("Usulan mitra \"{$record->nama_mitra}\" berhasil diperbarui dan diajukan kembali untuk ditinjau.")
                ->success()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
