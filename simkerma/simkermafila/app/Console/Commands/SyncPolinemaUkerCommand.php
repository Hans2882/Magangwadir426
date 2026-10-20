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

            // Abaikan unit non-prodi dari API (misal UPT, Kategori umum)
            if (stripos($namaProdi, 'UPT') !== false || stripos($namaProdi, 'PROGRAM DIPLOMA LAIN') !== false || stripos($namaProdi, 'PROGRAM DILUAR DOMISILI') !== false) {
                continue;
            }

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

            // 3. Normalisasi jenjang (Sarjana Terapan <-> D-IV, D3 <-> D-III, D2) & PSDKU
            if (!$prodi) {
                $cleanApi = trim(preg_replace('/^(Sarjana Terapan|D-?IV|D-?III|D-?II|D-?I|S-?[1-3]|D4|D3|D2)\s+/i', '', $namaProdi));
                $psdkuApi = null;
                if (stripos($namaProdi, 'Kediri') !== false) $psdkuApi = 'Kediri';
                elseif (stripos($namaProdi, 'Lumajang') !== false) $psdkuApi = 'Lumajang';
                elseif (stripos($namaProdi, 'Pamekasan') !== false) $psdkuApi = 'Pamekasan';

                $isD3Api = stripos($namaProdi, 'D3') !== false || stripos($namaProdi, 'D-III') !== false;
                $isD4Api = stripos($namaProdi, 'Sarjana Terapan') !== false || stripos($namaProdi, 'D4') !== false || stripos($namaProdi, 'D-IV') !== false;

                // Prioritaskan mencari di 48 prodi asli bawaan SIMKERMA
                $existingProdis = MasterProgramStudi::orderBy('id', 'asc')->get();
                foreach ($existingProdis as $ep) {
                    $cleanLocal = trim(preg_replace('/^(Sarjana Terapan|D-?IV|D-?III|D-?II|D-?I|S-?[1-3]|D4|D3|D2)\s+/i', '', $ep->nama_prodi));
                    $psdkuLocal = null;
                    if (stripos($ep->nama_prodi, 'Kediri') !== false) $psdkuLocal = 'Kediri';
                    elseif (stripos($ep->nama_prodi, 'Lumajang') !== false) $psdkuLocal = 'Lumajang';
                    elseif (stripos($ep->nama_prodi, 'Pamekasan') !== false) $psdkuLocal = 'Pamekasan';

                    if ($psdkuApi !== $psdkuLocal) continue;

                    $isD3Local = stripos($ep->nama_prodi, 'D3') !== false || stripos($ep->nama_prodi, 'D-III') !== false;
                    $isD4Local = stripos($ep->nama_prodi, 'Sarjana Terapan') !== false || stripos($ep->nama_prodi, 'D4') !== false || stripos($ep->nama_prodi, 'D-IV') !== false;

                    if ($isD3Api !== $isD3Local || $isD4Api !== $isD4Local) continue;

                    $normL = strtolower(str_ireplace([' ', '-', ',', '.', 'psdku', 'kediri', 'lumajang', 'pamekasan', 'perangkat', '(', ')'], '', $cleanLocal));
                    $normA = strtolower(str_ireplace([' ', '-', ',', '.', 'psdku', 'kediri', 'lumajang', 'pamekasan', 'perangkat', '(', ')'], '', $cleanApi));

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
                $cleanFormattedName = preg_replace('/^D-?III\s+/i', 'D3 ', $namaProdi);
                $cleanFormattedName = preg_replace('/^D-?IV\s+/i', 'D4 ', $cleanFormattedName);
                $cleanFormattedName = preg_replace('/^D-?II\s+/i', 'D2 ', $cleanFormattedName);
                $cleanFormattedName = preg_replace('/^S-?2\s+/i', 'S2 ', $cleanFormattedName);
                $cleanFormattedName = preg_replace('/^S-?3\s+/i', 'S3 ', $cleanFormattedName);
                if (strtoupper($cleanFormattedName) === $cleanFormattedName) {
                    $cleanFormattedName = ucwords(strtolower($cleanFormattedName));
                    $cleanFormattedName = str_ireplace('Psdku', 'PSDKU', $cleanFormattedName);
                    $cleanFormattedName = preg_replace('/^D(\d)\s+/i', 'D$1 ', $cleanFormattedName);
                }

                MasterProgramStudi::create(array_merge($dataToUpdate, [
                    'nama_prodi' => $cleanFormattedName,
                    'nama_prodi_eng' => $item['singkatan'] ?? null,
                    'jurusan_id' => $localJurusanId,
                ]));
            }

            $syncedProdiCount++;
        }

        // Standardisasi nama D-III / D III menjadi D3 pada semua data prodi
        $d3Prodis = MasterProgramStudi::where('nama_prodi', 'LIKE', '%D-III%')
            ->orWhere('nama_prodi', 'LIKE', '%D III%')
            ->get();
        foreach ($d3Prodis as $dp) {
            $standardized = preg_replace('/^D-?III\s+/i', 'D3 ', $dp->nama_prodi);
            if (strtoupper($standardized) === $standardized) {
                $standardized = ucwords(strtolower($standardized));
                $standardized = str_ireplace('Psdku', 'PSDKU', $standardized);
                $standardized = preg_replace('/^D(\d)\s+/i', 'D$1 ', $standardized);
            }
            $dp->update(['nama_prodi' => $standardized]);
        }

        $this->info("Berhasil menyinkronkan {$syncedProdiCount} data Program Studi.");
        $this->newLine();
        $this->info('Sinkronisasi Unit Kerja Polinema selesai dengan sukses!');

        return Command::SUCCESS;
    }
}
