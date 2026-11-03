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
        ], $result);
    }
}
