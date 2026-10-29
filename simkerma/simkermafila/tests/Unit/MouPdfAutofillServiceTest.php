<?php

namespace Tests\Unit;

use App\Services\MouPdfAutofillService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

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

    public function test_it_uses_ollama_to_extract_only_values_supported_by_the_document(): void
    {
        config([
            'ollama-laravel.url' => 'http://127.0.0.1:11434',
            'ollama-laravel.model' => 'qwen2.5:7b',
            'ollama-laravel.connection.timeout' => 180,
            'ollama-laravel.keep_alive' => '30m',
        ]);

        Http::fake([
            'http://127.0.0.1:11434/api/generate' => Http::response([
                'response' => json_encode([
                    'judul' => 'KERJA SAMA PENDIDIKAN',
                    'nomor_dokumen_polinema' => '01/PL2/TU/2026',
                    'nomor_dokumen_mitra' => '02/MITRA/2026',
                    'tanggal_awal' => '2026-01-01',
                    'tanggal_akhir' => '2026-12-31',
                ], JSON_THROW_ON_ERROR),
            ]),
        ]);

        $text = "Nomor: 01/PL2/TU/2026\n"
            ."Nomor: 02/MITRA/2026\n"
            ."TENTANG\nKERJA SAMA PENDIDIKAN\n"
            .'Perjanjian berlaku mulai 1 Januari 2026 sampai dengan 31 Desember 2026.';

        $data = (new MouPdfAutofillService)->extractWithOllama($text);

        $this->assertSame([
            'judul' => 'KERJA SAMA PENDIDIKAN',
            'nomor_dokumen_polinema' => '01/PL2/TU/2026',
            'nomor_dokumen_mitra' => '02/MITRA/2026',
            'tanggal_awal' => '2026-01-01',
            'tanggal_akhir' => '2026-12-31',
        ], $data);

        Http::assertSent(fn ($request) => $request->url() === 'http://127.0.0.1:11434/api/generate'
            && $request['model'] === 'qwen2.5:7b'
            && $request['keep_alive'] === '30m');
    }

    public function test_it_uses_context_to_classify_document_numbers_with_unfamiliar_labels(): void
    {
        config([
            'ollama-laravel.url' => 'http://127.0.0.1:11434',
            'ollama-laravel.model' => 'qwen2.5:7b',
        ]);

        Http::fake([
            'http://127.0.0.1:11434/api/generate' => Http::response([
                'response' => json_encode([
                    'judul' => null,
                    'nomor_dokumen_polinema' => '142/PL2/HK.01.00/2026',
                    'nomor_dokumen_mitra' => '017/NTD-MOU/X/2026',
                    'tanggal_awal' => null,
                    'tanggal_akhir' => null,
                ], JSON_THROW_ON_ERROR),
            ]),
        ]);

        $text = "Administrasi pihak perguruan tinggi\n142/PL2/HK.01.00/2026\n"
            ."Administrasi pihak mitra\n017/NTD-MOU/X/2026";
        $data = (new MouPdfAutofillService)->extractWithOllama($text);

        $this->assertSame('142/PL2/HK.01.00/2026', $data['nomor_dokumen_polinema']);
        $this->assertSame('017/NTD-MOU/X/2026', $data['nomor_dokumen_mitra']);

        Http::assertSent(fn ($request) => str_contains(
            $request['prompt'],
            'do not assign numbers only by their order'
        ));
    }

    public function test_it_rejects_ollama_values_not_supported_by_the_document(): void
    {
        config([
            'ollama-laravel.url' => 'http://127.0.0.1:11434',
            'ollama-laravel.model' => 'qwen2.5:7b',
        ]);

        Http::fake([
            'http://127.0.0.1:11434/api/generate' => Http::response([
                'response' => json_encode([
                    'judul' => 'A DIFFERENT AGREEMENT',
                    'nomor_dokumen_polinema' => '99/FAKE/2026',
                    'tanggal_awal' => '2026-04-01',
                    'tanggal_akhir' => '2026-12-31',
                ], JSON_THROW_ON_ERROR),
            ]),
        ]);

        $data = (new MouPdfAutofillService)->extractWithOllama(
            "TENTANG\nKERJA SAMA PENDIDIKAN\nDitandatangani pada 2 Februari 2026."
        );

        $this->assertSame([], $data);
    }
}
