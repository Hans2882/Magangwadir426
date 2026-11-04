<?php

namespace Tests\Unit;

use App\Filament\Resources\MouResource;
use ReflectionMethod;
use Tests\TestCase;

class MouResourceAutofillTest extends TestCase
{
    public function test_it_fills_only_empty_fields_and_preserves_existing_values(): void
    {
        $current = [
            'judul' => 'Judul yang ditulis pengguna',
            'nomor_dokumen_polinema' => null,
            'nomor_dokumen_mitra' => '',
            'tanggal_awal' => '2026-02-01',
            'tanggal_akhir' => null,
        ];
        $changes = [];
        $method = new ReflectionMethod(MouResource::class, 'applyAutofillData');
        $method->setAccessible(true);

        $result = $method->invoke(
            null,
            [
                'judul' => 'Judul dari PDF',
                'nomor_dokumen_polinema' => '01/PL2/2026',
                'nomor_dokumen_mitra' => '02/MITRA/2026',
                'tanggal_awal' => '2026-01-01',
                'tanggal_akhir' => '2027-01-01',
                '_evidence' => [
                    'nomor_dokumen_polinema' => [
                        'page' => 1,
                        'quote' => 'Nomor Polinema: 01/PL2/2026',
                    ],
                    'nomor_dokumen_mitra' => [
                        'page' => 1,
                        'quote' => 'Nomor Mitra: 02/MITRA/2026',
                    ],
                    'tanggal_akhir' => [
                        'page' => 2,
                        'quote' => 'berlaku sampai dengan 1 Januari 2027',
                    ],
                ],
            ],
            function (string $field, mixed $value) use (&$current, &$changes): void {
                $current[$field] = $value;
                $changes[$field] = $value;
            },
            fn (string $field) => $current[$field] ?? null,
        );

        $this->assertSame([
            'nomor_dokumen_polinema' => '01/PL2/2026',
            'nomor_dokumen_mitra' => '02/MITRA/2026',
            'tanggal_akhir' => '2027-01-01',
        ], $changes);
        $this->assertSame('Judul yang ditulis pengguna', $current['judul']);
        $this->assertSame('2026-02-01', $current['tanggal_awal']);
        $this->assertSame([
            'filled' => ['No. Polinema', 'No. Mitra', 'Tgl. Berakhir'],
            'preserved' => ['Judul', 'Tgl. Berlaku'],
            'evidence' => [
                [
                    'field' => 'No. Polinema',
                    'page' => 1,
                    'quote' => 'Nomor Polinema: 01/PL2/2026',
                ],
                [
                    'field' => 'No. Mitra',
                    'page' => 1,
                    'quote' => 'Nomor Mitra: 02/MITRA/2026',
                ],
                [
                    'field' => 'Tgl. Berakhir',
                    'page' => 2,
                    'quote' => 'berlaku sampai dengan 1 Januari 2027',
                ],
            ],
        ], $result);
    }

    public function test_notification_body_shows_page_citations_for_filled_fields(): void
    {
        $method = new ReflectionMethod(MouResource::class, 'autofillNotificationBody');
        $method->setAccessible(true);

        $body = $method->invoke(null, [
            'filled' => ['Tgl. Berlaku'],
            'evidence' => [
                [
                    'field' => 'Tgl. Berlaku',
                    'page' => 2,
                    'quote' => 'berlaku mulai 1 Maret 2026',
                ],
            ],
        ]);

        $this->assertStringContainsString('halaman 2', $body);
        $this->assertStringContainsString('berlaku mulai 1 Maret 2026', $body);
    }
}
