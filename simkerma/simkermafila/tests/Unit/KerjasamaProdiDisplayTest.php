<?php

namespace Tests\Unit;

use App\Models\Kerjasama;
use PHPUnit\Framework\Attributes\Test;

class KerjasamaProdiDisplayTest extends \Tests\TestCase
{
    #[Test]
    public function it_returns_only_one_display_value_for_prodi_names(): void
    {
        $kerjasama = new Kerjasama();

        $kerjasama->setRelation('prodis', collect([
            (object) ['nama_prodi' => 'Teknik Informatika'],
            (object) ['nama_prodi' => 'Teknik Informatika'],
            (object) ['nama_prodi' => 'Sistem Informasi'],
        ]));

        $this->assertSame('Teknik Informatika', $kerjasama->prodi_display_name);
    }
}
