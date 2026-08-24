<?php

namespace App\Filament\Resources\UsulanKerjasamas\Pages;

use App\Filament\Resources\UsulanKerjasamas\UsulanKerjasamaResource;
use Filament\Resources\Pages\CreateRecord;

use App\Models\MasterKegiatan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateUsulanKerjasama extends CreateRecord
{
    protected static string $resource = UsulanKerjasamaResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;
        
        // Fetch the related activities
        $kegiatans = $record->kegiatans;

        // Generate PDF
        $pdf = app('dompdf.wrapper')->loadView('pdf.berita-acara-inisiasi', [
            'record' => $record,
            'kegiatans' => $kegiatans
        ]);

        // Clean filename and upload to Google Drive
        $cleanMitra = Str::slug($record->usulan_nama_mitra ?? 'Mitra');
        $filename = "Berita_Acara_Inisiasi_{$cleanMitra}_" . time() . ".pdf";
        $directory = 'Usulan Inisiasi/' . date('Y/m/d');
        $path = $directory . '/' . $filename;

        Storage::disk('google')->put($path, $pdf->output());

        // Save path to record
        $record->update(['dokumen_pendukung' => $path]);
    }
}
