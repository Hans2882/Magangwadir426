<?php

namespace App\Console\Commands;

use App\Models\MasterJurusan;
use App\Models\MasterProgramStudi;
use App\Services\PolinemaApiService;
use Illuminate\Console\Command;

class SyncPolinemaUkerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'polinema:sync-uker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi data Master Jurusan dan Program Studi dari API Polinema (Bidang IV)';

    /**
     * Execute the console command.
     */
    public function handle(PolinemaApiService $apiService): int
    {
        $this->info('Memulai sinkronisasi Unit Kerja dari API Polinema...');

        if (!$apiService->isConfigured()) {
            $this->warn('Kredensial API Polinema belum diisi pada file .env.');
            $this->warn('Pastikan POLINEMA_API_USERNAME dan POLINEMA_API_PASSWORD sudah terisi.');
            return Command::FAILURE;
        }

        // 1. Sinkronisasi Jurusan
        $this->info('Mengambil data Jurusan dari API...');
        $jurusans = $apiService->getJurusanList();

        if (empty($jurusans)) {
            // Coba ambil dari hirarki jika endpoint terpisah kosong
            $hirarki = $apiService->getHirarkiUker();
            if (!empty($hirarki)) {
                $jurusans = $hirarki;
            }
        }

        $syncedJurusanCount = 0;
        $jurusanMap = []; // [api_id => local_id]

        foreach ($jurusans as $item) {
            $apiId = (string) ($item['id'] ?? '');
            $namaJurusan = trim($item['nama'] ?? $item['nama_unit'] ?? '');

            if (empty($namaJurusan)) {
                continue;
            }

            // Cari jurusan lokal berdasarkan integrasi_jurusan atau kesamaan nama
            $jurusan = MasterJurusan::where('integrasi_jurusan', $apiId)
                ->orWhere('nama_jurusan', 'LIKE', '%' . $namaJurusan . '%')
                ->first();

            $dataToUpdate = [
                'integrasi_jurusan' => $apiId,
                'nama_pimpinan' => $item['nama_pimpinan'] ?? $jurusan?->nama_pimpinan,
                'nip_pimpinan' => $item['nip_pimpinan'] ?? $jurusan?->nip_pimpinan,
            ];

            if ($jurusan) {
                $jurusan->update($dataToUpdate);
            } else {
                $jurusan = MasterJurusan::create(array_merge($dataToUpdate, [
                    'nama_jurusan' => $namaJurusan,
                    'nama_jurusan_eng' => $item['singkatan'] ?? null,
                ]));
            }

            $jurusanMap[$apiId] = $jurusan->id;
            $syncedJurusanCount++;
        }

        $this->info("Berhasil menyinkronkan {$syncedJurusanCount} data Jurusan.");

        // 2. Sinkronisasi Program Studi
        $this->info('Mengambil data Program Studi dari API...');
        $prodis = $apiService->getProdiList();
        $syncedProdiCount = 0;

        foreach ($prodis as $item) {
            $apiProdiId = (string) ($item['id'] ?? '');
            $namaProdi = trim($item['nama'] ?? $item['nama_unit'] ?? '');
            $idInduk = (string) ($item['id_induk'] ?? '');

            if (empty($namaProdi)) {
                continue;
            }

            // Hubungkan ke jurusan_id lokal jika id_induk cocok
            $localJurusanId = $jurusanMap[$idInduk] ?? null;

            // Cari prodi lokal berdasarkan integrasi_prodi atau kemiripan nama
            $prodi = MasterProgramStudi::where('integrasi_prodi', $apiProdiId)
                ->orWhere('nama_prodi', 'LIKE', '%' . $namaProdi . '%')
                ->first();

            $dataToUpdate = [
                'integrasi_prodi' => $apiProdiId,
            ];

            if ($localJurusanId && (!$prodi || !$prodi->jurusan_id)) {
                $dataToUpdate['jurusan_id'] = $localJurusanId;
            }

            if ($prodi) {
                $prodi->update($dataToUpdate);
            } else {
                MasterProgramStudi::create(array_merge($dataToUpdate, [
                    'nama_prodi' => $namaProdi,
                    'nama_prodi_eng' => $item['singkatan'] ?? null,
                    'jurusan_id' => $localJurusanId,
                ]));
            }

            $syncedProdiCount++;
        }

        $this->info("Berhasil menyinkronkan {$syncedProdiCount} data Program Studi.");
        $this->newLine();
        $this->info('Sinkronisasi Unit Kerja Polinema selesai dengan sukses!');

        return Command::SUCCESS;
    }
}
