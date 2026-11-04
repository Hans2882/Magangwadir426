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

    public function test_it_does_not_treat_a_signature_date_as_the_start_date(): void
    {
        $service = new MouPdfAutofillService;
        $data = $service->extractFields("TENTANG KERJA SAMA\nDitandatangani pada 2 Februari 2026.");

        $this->assertNull($data['tanggal_awal']);
        $this->assertNull($data['tanggal_akhir']);
    }

    public function test_it_extracts_the_explicit_start_date_after_an_earlier_signature_date(): void
    {
        $data = (new MouPdfAutofillService)->extractFields(
            "Ditandatangani pada 2 Februari 2026.\n"
            .'Perjanjian berlaku mulai tanggal 1 Maret 2026 sampai dengan 28 Februari 2027.'
        );

        $this->assertSame('2026-03-01', $data['tanggal_awal']);
        $this->assertSame('2027-02-28', $data['tanggal_akhir']);
    }

    public function test_it_keeps_repeated_dates_when_the_later_occurrence_has_start_date_context(): void
    {
        $data = (new MouPdfAutofillService)->extractFields(
            "Ditandatangani pada 1 Maret 2026.\n"
            .'Perjanjian berlaku mulai 1 Maret 2026.'
        );

        $this->assertSame('2026-03-01', $data['tanggal_awal']);
    }

    public function test_extraction_is_incomplete_until_all_fields_are_found(): void
    {
        $service = new MouPdfAutofillService;
        $method = new \ReflectionMethod($service, 'isExtractionComplete');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($service, [
            'judul' => 'KERJA SAMA',
            'nomor_dokumen_polinema' => '01/PL2/2026',
            'nomor_dokumen_mitra' => null,
            'tanggal_awal' => '2026-01-01',
            'tanggal_akhir' => '2027-01-01',
        ]));

        $this->assertTrue($method->invoke($service, [
            'judul' => 'KERJA SAMA',
            'nomor_dokumen_polinema' => '01/PL2/2026',
            'nomor_dokumen_mitra' => '02/MITRA/2026',
            'tanggal_awal' => '2026-01-01',
            'tanggal_akhir' => '2027-01-01',
        ]));
    }

    public function test_it_returns_page_and_quote_evidence_for_extracted_fields(): void
    {
        $service = new MouPdfAutofillService;
        $method = new \ReflectionMethod($service, 'withEvidence');
        $method->setAccessible(true);

        $data = $method->invoke($service, [
            'judul' => 'KERJA SAMA PENDIDIKAN',
            'nomor_dokumen_polinema' => null,
            'nomor_dokumen_mitra' => null,
            'tanggal_awal' => '2026-03-01',
            'tanggal_akhir' => null,
        ], [
            1 => 'Ditandatangani pada 2 Februari 2026.',
            2 => "TENTANG\nKERJA SAMA PENDIDIKAN\nPerjanjian berlaku mulai\n1 Maret 2026.",
        ]);

        $this->assertSame(2, $data['_evidence']['judul']['page']);
        $this->assertStringContainsString(
            'KERJA SAMA PENDIDIKAN',
            $data['_evidence']['judul']['quote']
        );
        $this->assertSame(2, $data['_evidence']['tanggal_awal']['page']);
        $this->assertStringContainsString(
            'berlaku mulai 1 Maret 2026',
            $data['_evidence']['tanggal_awal']['quote']
        );
    }

    public function test_it_uses_ollama_to_extract_only_values_supported_by_the_document(): void
    {
        config([
            'ollama-laravel.enabled' => true,
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
            'ollama-laravel.enabled' => true,
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
            'Never assign document numbers based only on their order.'
        ));
    }

    public function test_ollama_input_keeps_the_beginning_and_end_of_long_documents(): void
    {
        config([
            'ollama-laravel.enabled' => true,
            'ollama-laravel.url' => 'http://127.0.0.1:11434',
            'ollama-laravel.model' => 'qwen2.5:7b',
        ]);

        Http::fake([
            'http://127.0.0.1:11434/api/generate' => Http::response([
                'response' => json_encode([
                    'judul' => null,
                    'nomor_dokumen_polinema' => null,
                    'nomor_dokumen_mitra' => null,
                    'tanggal_awal' => null,
                    'tanggal_akhir' => null,
                ], JSON_THROW_ON_ERROR),
            ]),
        ]);

        $text = 'AWAL-DOKUMEN ' . str_repeat('teks ', 6000) . 'AKHIR-DOKUMEN';
        (new MouPdfAutofillService)->extractWithOllama($text);

        Http::assertSent(fn ($request) => str_contains($request['prompt'], 'AWAL-DOKUMEN')
            && str_contains($request['prompt'], 'AKHIR-DOKUMEN')
            && str_contains($request['prompt'], '[Bagian tengah dokumen dilewati]'));
    }

    public function test_it_rejects_ollama_values_not_supported_by_the_document(): void
    {
        config([
            'ollama-laravel.enabled' => true,
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

    public function test_it_does_not_send_text_to_ollama_when_disabled(): void
    {
        config(['ollama-laravel.enabled' => false]);
        Http::fake();

        $data = (new MouPdfAutofillService)->extractWithOllama('Teks dokumen rahasia.');
        $warmupResult = (new MouPdfAutofillService)->warmUpOllama();

        $this->assertNull($data);
        $this->assertFalse($warmupResult);
        Http::assertNothingSent();
    }

    public function test_local_and_ai_extraction_use_separate_cache_entries(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'mou-cache-test-');
        file_put_contents($path, 'same PDF contents');

        try {
            $method = new \ReflectionMethod(MouPdfAutofillService::class, 'cacheKey');
            $method->setAccessible(true);
            $service = new MouPdfAutofillService;

            $localKey = $method->invoke($service, $path, false);
            $aiKey = $method->invoke($service, $path, true);

            $this->assertNotSame($localKey, $aiKey);
            $this->assertStringEndsWith(':local', $localKey);
            $this->assertStringEndsWith(':ai', $aiKey);
        } finally {
            @unlink($path);
        }
    }
}
