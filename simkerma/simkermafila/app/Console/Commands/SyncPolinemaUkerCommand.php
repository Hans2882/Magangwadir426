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
            $apiSiakadId = (string) ($item['id_siakad'] ?? '');
            $namaProdi = trim($item['nama'] ?? $item['nama_unit'] ?? '');
            $idInduk = (string) ($item['id_induk'] ?? '');

            if (empty($namaProdi)) {
                continue;
            }

            // Hubungkan ke jurusan_id lokal jika id_induk cocok
            $localJurusanId = $jurusanMap[$idInduk] ?? null;

            // Cari prodi lokal:
            // 1. Berdasarkan integrasi_prodi yang sudah tersimpan
            $prodi = MasterProgramStudi::where(function ($q) use ($apiSiakadId, $apiProdiId) {
                if (!empty($apiSiakadId)) {
                    $q->where('integrasi_prodi', $apiSiakadId);
                }
                if (!empty($apiProdiId)) {
                    $q->orWhere('integrasi_prodi', $apiProdiId);
                }
            })->first();

            // 2. Berdasarkan kesamaan nama persis
            if (!$prodi) {
                $prodi = MasterProgramStudi::where('nama_prodi', 'LIKE', '%' . $namaProdi . '%')->first();
            }

            // 3. Normalisasi jenjang (Sarjana Terapan <-> D-IV, D3 <-> D-III) & PSDKU
            if (!$prodi) {
                $cleanApi = trim(preg_replace('/^(Sarjana Terapan|D-?IV|D-?III|D-?II|D-?I|S-?[1-3]|D3|D4)\s+/i', '', $namaProdi));
                $psdkuApi = null;
                if (stripos($namaProdi, 'Kediri') !== false) $psdkuApi = 'Kediri';
                elseif (stripos($namaProdi, 'Lumajang') !== false) $psdkuApi = 'Lumajang';
                elseif (stripos($namaProdi, 'Pamekasan') !== false) $psdkuApi = 'Pamekasan';

                $isD3Api = stripos($namaProdi, 'D3') !== false || stripos($namaProdi, 'D-III') !== false;
                $isD4Api = stripos($namaProdi, 'Sarjana Terapan') !== false || stripos($namaProdi, 'D4') !== false || stripos($namaProdi, 'D-IV') !== false;

                $existingProdis = MasterProgramStudi::all();
                foreach ($existingProdis as $ep) {
                    $cleanLocal = trim(preg_replace('/^(Sarjana Terapan|D-?IV|D-?III|D-?II|D-?I|S-?[1-3]|D3|D4)\s+/i', '', $ep->nama_prodi));
                    $psdkuLocal = null;
                    if (stripos($ep->nama_prodi, 'Kediri') !== false) $psdkuLocal = 'Kediri';
                    elseif (stripos($ep->nama_prodi, 'Lumajang') !== false) $psdkuLocal = 'Lumajang';
                    elseif (stripos($ep->nama_prodi, 'Pamekasan') !== false) $psdkuLocal = 'Pamekasan';

                    if ($psdkuApi !== $psdkuLocal) continue;

                    $isD3Local = stripos($ep->nama_prodi, 'D3') !== false || stripos($ep->nama_prodi, 'D-III') !== false;
                    $isD4Local = stripos($ep->nama_prodi, 'Sarjana Terapan') !== false || stripos($ep->nama_prodi, 'D4') !== false || stripos($ep->nama_prodi, 'D-IV') !== false;

                    if ($isD3Api !== $isD3Local || $isD4Api !== $isD4Local) continue;

                    $normL = strtolower(str_replace([' ', '-', ',', '.'], '', $cleanLocal));
                    $normA = strtolower(str_replace([' ', '-', ',', '.'], '', $cleanApi));

                    if ($normL === $normA || stripos($normA, $normL) !== false || stripos($normL, $normA) !== false) {
                        $prodi = $ep;
                        break;
                    }
                }
            }

            $dataToUpdate = [
                'integrasi_prodi' => !empty($apiSiakadId) ? $apiSiakadId : $apiProdiId,
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
