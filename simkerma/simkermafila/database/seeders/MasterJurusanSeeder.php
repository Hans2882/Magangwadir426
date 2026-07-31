<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MasterJurusanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jurusans = [
            'Jurusan Administrasi Niaga',
            'Jurusan Akuntansi',
            'Jurusan Teknik Kimia',
            'Jurusan Teknik Mesin',
            'Jurusan Teknik Sipil',
            'Jurusan Teknik Elektro',
            'Jurusan Teknologi Informasi',
        ];

        foreach ($jurusans as $jurusan) {
            \App\Models\MasterJurusan::firstOrCreate(['nama_jurusan' => $jurusan]);
        }
    }
}
