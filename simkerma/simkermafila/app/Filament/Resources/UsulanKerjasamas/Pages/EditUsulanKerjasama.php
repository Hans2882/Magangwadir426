<?php

namespace App\Filament\Resources\UsulanKerjasamas\Pages;

use App\Filament\Resources\UsulanKerjasamas\UsulanKerjasamaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUsulanKerjasama extends EditRecord
{
    protected static string $resource = UsulanKerjasamaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        
        // Fetch the related activities
        $kegiatans = $record->kegiatans;

        // Custom activities stored on the proposal row only (not in master_kegiatan)
        $kegiatanLainnya = $record->kegiatan_lainnya ?? [];

        // Generate PDF
        $pdf = app('dompdf.wrapper')->loadView('pdf.berita-acara-inisiasi', [
            'record' => $record,
            'kegiatans' => $kegiatans,
            'kegiatanLainnya' => $kegiatanLainnya,
        ]);

        // Clean filename and upload to Google Drive
        $cleanMitra = \Illuminate\Support\Str::slug($record->usulan_nama_mitra ?? 'Mitra');
        $filename = "Berita_Acara_Inisiasi_{$cleanMitra}_" . time() . ".pdf";
        $directory = 'Usulan Inisiasi/' . date('Y/m/d');
        $path = $directory . '/' . $filename;

        \Illuminate\Support\Facades\Storage::disk('google')->put($path, $pdf->output());

        // We can optionally delete the old one from Drive, but for now we just update the path
        $record->update(['dokumen_pendukung' => $path]);
    }
}
