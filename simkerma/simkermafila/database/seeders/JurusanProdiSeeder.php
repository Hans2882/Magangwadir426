<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterJurusan;
use App\Models\MasterProgramStudi;

class JurusanProdiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jurusans = MasterJurusan::all();
        $prodis = MasterProgramStudi::all();

        // Dictionary of keywords to match a Prodi to a Jurusan
        $mapping = [
            'Administrasi' => 'Jurusan Administrasi Niaga',
            'Akuntansi' => 'Jurusan Akuntansi',
            'Keuangan' => 'Jurusan Akuntansi',
            'Informatika' => 'Jurusan Teknologi Informasi',
            'Sistem Informasi' => 'Jurusan Teknologi Informasi',
            'Mesin' => 'Jurusan Teknik Mesin',
            'Otomotif' => 'Jurusan Teknik Mesin',
            'Sipil' => 'Jurusan Teknik Sipil',
            'Pertambangan' => 'Jurusan Teknik Sipil',
            'Konstruksi' => 'Jurusan Teknik Sipil',
            'Elektro' => 'Jurusan Teknik Elektro',
            'Listrik' => 'Jurusan Teknik Elektro',
            'Telekomunikasi' => 'Jurusan Teknik Elektro',
            'Kelistrikan' => 'Jurusan Teknik Elektro',
            'Kimia' => 'Jurusan Teknik Kimia',
            'Bahasa Inggris' => 'Jurusan Administrasi Niaga',
            'Arsip' => 'Jurusan Administrasi Niaga',
        ];

        $mappedCount = 0;

        foreach ($prodis as $prodi) {
            $matchedJurusanName = null;
            
            // Find which Jurusan matches the Prodi's name
            foreach ($mapping as $keyword => $jurusanName) {
                if (stripos($prodi->nama_prodi, $keyword) !== false) {
                    $matchedJurusanName = $jurusanName;
                    break;
                }
            }

            if ($matchedJurusanName) {
                $jurusan = $jurusans->where('nama_jurusan', $matchedJurusanName)->first();
                if ($jurusan) {
                    $prodi->update(['jurusan_id' => $jurusan->id]);
                    $mappedCount++;
                }
            }
        }

        $this->command->info("Berhasil memetakan {$mappedCount} Program Studi ke Jurusan masing-masing.");
    }
}
