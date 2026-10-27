<?php

namespace Tests\Unit;

use App\Services\MouPdfAutofillService;
use PHPUnit\Framework\TestCase;

class MouPdfAutofillServiceTest extends TestCase
{
    public function test_it_extracts_mou_fields_from_searchable_pdf_text(): void
    {
        $service = new MouPdfAutofillService;
        $data = $service->extractFields(
            "Nomor: 01/PL2/TU/2026\n"
            ."Nomor: 02/MITRA/2026\n"
            ."TENTANG\n"
            ."KERJA SAMA PENDIDIKAN\n"
            ."PIHAK PERTAMA\n"
            .'Perjanjian berlaku mulai 1 Januari 2026 sampai dengan 31 Desember 2030.'
        );

        $this->assertSame('KERJA SAMA PENDIDIKAN', $data['judul']);
        $this->assertSame('01/PL2/TU/2026', $data['nomor_dokumen_polinema']);
        $this->assertSame('02/MITRA/2026', $data['nomor_dokumen_mitra']);
        $this->assertSame('2026-01-01', $data['tanggal_awal']);
        $this->assertSame('2030-12-31', $data['tanggal_akhir']);
    }

    public function test_it_does_not_guess_an_end_date_without_explicit_end_language(): void
    {
        $service = new MouPdfAutofillService;
        $data = $service->extractFields("TENTANG KERJA SAMA\nDitandatangani pada 2 Februari 2026.");

        $this->assertSame('2026-02-02', $data['tanggal_awal']);
        $this->assertNull($data['tanggal_akhir']);
    }
}
