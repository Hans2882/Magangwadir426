<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\MasterJurusan;
use App\Models\MasterProgramStudi;
use App\Models\Privilege;
use App\Models\UserPrivilege;
use App\Models\UserProgramStudi;

class PolinemaApiService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected bool $enabled;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.polinema.base_url', 'http://api.polinema.ac.id/bidang_iv'), '/');
        $this->username = config('services.polinema.username', '');
        $this->password = config('services.polinema.password', '');
        $this->enabled = (bool) config('services.polinema.enabled', true);
    }

    public function isConfigured(): bool
    {
        return $this->enabled && !empty($this->username) && !empty($this->password);
    }

    protected function client()
    {
        return Http::withBasicAuth($this->username, $this->password)
            ->timeout(10)
            ->withoutVerifying();
    }

    /**
     * Autentikasi Pengguna via SSO Polinema
     *
     * @param string $username NIM (Mahasiswa) atau NIP (Dosen/Tendik)
     * @param string $password Password SSO
     * @return array [
     *    'success' => bool,
     *    'error_type' => 'credentials'|'inactive'|'network'|null,
     *    'message' => string,
     *    'data' => array|null
     * ]
     */
    public function login(string $username, string $password): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error_type' => 'not_configured',
                'message' => 'API SSO Polinema belum dikonfigurasi pada file .env',
                'data' => null,
            ];
        }

        try {
            $response = $this->client()->post("{$this->baseUrl}/login/index", [
                'username' => trim($username),
                'password' => $password,
            ]);

            if (!$response->successful()) {
                Log::warning('Polinema SSO HTTP Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [
                    'success' => false,
                    'error_type' => 'network',
                    'message' => 'Gagal terhubung ke server SSO Polinema (HTTP ' . $response->status() . ')',
                    'data' => null,
                ];
            }

            $json = $response->json();
            $status = strtolower($json['status'] ?? '');

            if ($status !== 'success') {
                return [
                    'success' => false,
                    'error_type' => 'credentials',
                    'message' => $json['message'] ?? 'NIM/NIP atau Password salah.',
                    'data' => null,
                ];
            }

            $rawPayload = $json['data'] ?? [];
            if (is_array($rawPayload) && isset($rawPayload[0]) && is_array($rawPayload[0])) {
                $data = $rawPayload[0];
            } elseif (is_array($rawPayload)) {
                $data = $rawPayload;
            } else {
                $data = [];
            }

            $userStatus = strtolower(trim((string) ($data['user_status'] ?? '')));
            $loginStatus = strtolower(trim((string) ($data['login_status'] ?? '')));

            // Evaluasi status keaktifan akun
            // Valid jika user_status aktif/active dan login_status valid/active
            $isUserActive = in_array($userStatus, ['aktif', 'active', '1', 'true']);
            $isLoginValid = in_array($loginStatus, ['valid', 'aktif', 'active', '1', 'true']);

            if (!$isUserActive || !$isLoginValid) {
                return [
                    'success' => false,
                    'error_type' => 'inactive',
                    'message' => 'Akun Anda terdaftar tetapi berstatus TIDAK AKTIF di Polinema. Silakan hubungi bagian Akademik / Kepegawaian Polinema.',
                    'data' => $data,
                ];
            }

            return [
                'success' => true,
                'error_type' => null,
                'message' => 'Login SSO berhasil.',
                'data' => $data,
            ];

        } catch (\Throwable $e) {
            Log::error('Polinema SSO Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error_type' => 'network',
                'message' => 'Terjadi gangguan saat menghubungkan ke SSO Polinema: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Memeriksa data mahasiswa aktif semester berjalan
     */
    public function checkMahasiswaAktif(string $nim): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->client()->post("{$this->baseUrl}/mahasiswa/aktif", [
                'cari' => trim($nim),
                'nim' => trim($nim),
                'modedata' => 'utama',
            ]);

            if ($response->successful()) {
                $json = $response->json();
                if (($json['status'] ?? '') === 'success' && !empty($json['data'])) {
                    $items = is_array($json['data']) ? $json['data'] : [];
                    // Jika data berformat array asosiatif tunggal
                    if (isset($items['nim'])) {
                        return $items;
                    }
                    // Jika array kumpulan data
                    foreach ($items as $item) {
                        if (strcasecmp($item['nim'] ?? '', $nim) === 0) {
                            return $item;
                        }
                    }
                    return $items[0] ?? null;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('checkMahasiswaAktif error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Mengambil detail data mahasiswa berdasarkan NIM
     */
    public function getMahasiswa(string $nim): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->client()->post("{$this->baseUrl}/mahasiswa/index", [
                'cari' => trim($nim),
                'nim' => trim($nim),
                'modedata' => 'utama',
            ]);

            if ($response->successful()) {
                $json = $response->json();
                if (($json['status'] ?? '') === 'success' && !empty($json['data'])) {
                    $items = is_array($json['data']) ? $json['data'] : [];
                    if (isset($items['nim'])) {
                        return $items;
                    }
                    foreach ($items as $item) {
                        if (strcasecmp($item['nim'] ?? '', $nim) === 0) {
                            return $item;
                        }
                    }
                    return $items[0] ?? null;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('getMahasiswa error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Mengambil detail data pegawai berdasarkan NIP, Nama, atau Username SSO
     *
     * @param string $identifier NIP (18 digit), Username SSO, atau Email
     * @param string|null $name Nama lengkap dari SSO untuk bantuan pencarian
     * @return array|null Data pegawai lengkap (biodata, nomor_identitas, riwayat, id)
     */
    public function getPegawaiDetail(string $identifier, ?string $name = null): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $identifier = trim($identifier);
        $cleanSearch = $identifier;

        // Jika berupa email, ambil username/prefix
        if (str_contains($cleanSearch, '@')) {
            $cleanSearch = explode('@', $cleanSearch)[0];
        }

        // Tentukan kata kunci pencarian utama dan cadangan
        $searchQueries = [];
        if (preg_match('/^\d+$/', $cleanSearch)) {
            // NIP angka
            $searchQueries[] = $cleanSearch;
        } else {
            // Jika ada nama lengkap dari SSO, prioritaskan nama lengkap
            if (!empty($name)) {
                // Bersihkan gelar umum di awal nama jika ada
                $cleanName = preg_replace('/^(prof\.|dr\.|ir\.|drs\.)\s+/i', '', trim($name));
                $searchQueries[] = $cleanName;
            }
            // Ubah titik/strip/underscore pada username email menjadi spasi (contoh: rosa.andrie -> rosa andrie)
            $searchQueries[] = str_replace(['.', '_', '-'], ' ', $cleanSearch);
            $searchQueries[] = $cleanSearch;
        }

        $searchQueries = array_unique(array_filter($searchQueries));

        foreach ($searchQueries as $query) {
            try {
                $response = $this->client()->post("{$this->baseUrl}/pegawai/index", [
                    'search' => $query,
                    'limit' => 20,
                    'start' => 0,
                    'riwayat' => ['jabstruk'],
                ]);

                if ($response->successful()) {
                    $json = $response->json();
                    if (($json['status'] ?? '') === 'success' && !empty($json['data'])) {
                        $items = is_array($json['data']) ? $json['data'] : [];

                        // 1. Coba cari yang NIP atau email_institusi atau namanya cocok persis
                        foreach ($items as $item) {
                            $nipBaru = $item['nomor_identitas']['nipBaru'] ?? '';
                            $nipLama = $item['nomor_identitas']['nipLama'] ?? '';
                            $emailInstansi = $item['biodata']['email_institusi'] ?? '';
                            $namaPegawai = $item['biodata']['namaPegawai'] ?? '';

                            if ($identifier === $nipBaru || $identifier === $nipLama) {
                                return $item;
                            }

                            if (!empty($emailInstansi) && (
                                strcasecmp($emailInstansi, $identifier) === 0 ||
                                str_starts_with(strtolower($identifier), strtolower(explode('@', $emailInstansi)[0])) ||
                                str_starts_with(strtolower($emailInstansi), strtolower($cleanSearch . '@'))
                            )) {
                                return $item;
                            }

                            if (!empty($name) && stripos($namaPegawai, trim($name)) !== false) {
                                return $item;
                            }
                        }

                        // Jika query menghasilkan data tunggal atau beberapa hasil
                        return $items[0] ?? null;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("getPegawaiDetail error for query '{$query}': " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Menyinkronkan data dari API Kepegawaian Polinema ke model User
     * Mengatur NIP, Nama Gelar, Jurusan, Program Studi, dan Privilege
     */
    public function syncPegawaiDataToUser(User $user, ?array $pegawaiInfo = null): User
    {
        if (empty($pegawaiInfo)) {
            $pegawaiInfo = $this->getPegawaiDetail($user->username ?? '', $user->name ?? null);
            if (empty($pegawaiInfo) && !empty($user->email)) {
                $pegawaiInfo = $this->getPegawaiDetail($user->email, $user->name ?? null);
            }
        }

        if (empty($pegawaiInfo)) {
            return $user;
        }

        $biodata = $pegawaiInfo['biodata'] ?? [];
        $identitas = $pegawaiInfo['nomor_identitas'] ?? [];
        $riwayat = $pegawaiInfo['riwayat'] ?? [];
        $idData = $pegawaiInfo['id'] ?? [];

        $nip = trim((string) ($identitas['nipBaru'] ?? $identitas['nipLama'] ?? ''));
        $namaGelar = trim((string) ($biodata['namaGelar'] ?? $biodata['namaGelar2'] ?? ''));
        $penempatan = trim((string) ($biodata['penempatan'] ?? $biodata['namaUker'] ?? ''));
        $idUker = $idData['iduker'] ?? null;
        $homebase = trim((string) ($biodata['homebase'] ?? ''));
        $namaPekerjaan = strtolower(trim((string) ($biodata['namaPekerjaan'] ?? '')));

        // 1. Update username ke NIP (18 digit) dan nama bergelar
        $updates = [];
        if (!empty($nip) && preg_match('/^\d{18}$/', $nip)) {
            // Pastikan tidak ada user lain yang menggunakan NIP ini sebagai username
            $conflictUser = User::where('username', $nip)->where('id', '!=', $user->id)->first();
            if (!$conflictUser) {
                $updates['username'] = $nip;
            }
        }
        if (!empty($namaGelar) && $user->name !== $namaGelar) {
            $updates['name'] = $namaGelar;
        }

        // 2. Pemetaan Jurusan
        $jurusan = null;
        if (!empty($idUker)) {
            $jurusan = MasterJurusan::where('integrasi_jurusan', $idUker)->first();
        }
        if (!$jurusan && !empty($penempatan)) {
            $cleanJurusan = preg_replace('/^(jurusan\s+)/i', '', $penempatan);
            $cleanJurusan = trim($cleanJurusan);
            $jurusan = MasterJurusan::where('nama_jurusan', 'LIKE', '%' . $cleanJurusan . '%')->first();
        }
        if ($jurusan) {
            $updates['jurusan_id'] = $jurusan->id;
        }

        if (!empty($updates)) {
            $user->update($updates);
            $user->refresh();
        }

        // 3. Pemetaan Program Studi
        if (!empty($homebase)) {
            $cleanProdi = preg_replace('/^(d-?[i|v|x]+|s-?[1-3]|sarjana terapan)\s+/i', '', $homebase);
            $cleanProdi = str_ireplace('t. ', 'teknik ', $cleanProdi);
            $cleanProdi = trim($cleanProdi);

            $prodiQuery = MasterProgramStudi::query();
            if (!empty($user->jurusan_id)) {
                $prodiQuery->where('jurusan_id', $user->jurusan_id);
            }
            $prodi = $prodiQuery->where('nama_prodi', 'LIKE', '%' . $cleanProdi . '%')->orderBy('id', 'asc')->first();

            // Fallback jika tidak ditemukan dengan filter jurusan
            if (!$prodi) {
                $prodi = MasterProgramStudi::where('nama_prodi', 'LIKE', '%' . $cleanProdi . '%')->orderBy('id', 'asc')->first();
            }

            if ($prodi) {
                UserProgramStudi::updateOrCreate(
                    ['user_id' => $user->id],
                    ['program_studi_id' => $prodi->id]
                );

                if (!$user->jurusan_id && $prodi->jurusan_id) {
                    $user->update(['jurusan_id' => $prodi->jurusan_id]);
                }
            }
        }

        // 4. Deteksi Jabatan Struktural / Wadir 4 & Privilege
        $isWadir4 = false;
        $jabstrukList = $riwayat['jabstruk'] ?? [];
        $today = date('Y-m-d');
        foreach ($jabstrukList as $jab) {
            $namaJab = strtoupper($jab['namaJabStruk'] ?? $jab['namaJabatan'] ?? '');
            $tbt = $jab['tbtJabStruk'] ?? null;
            if ((str_contains($namaJab, 'WAKIL DIREKTUR IV') || str_contains($namaJab, 'WAKIL DIREKTUR 4')) && (empty($tbt) || $tbt >= $today)) {
                $isWadir4 = true;
                break;
            }
        }

        // Khusus pejabat Wadir 4 Polinema
        if ($nip === '198010102005011001' || str_contains(strtolower($user->email), 'rosa.andrie') || str_contains(strtolower($user->username ?? ''), 'rosa.andrie')) {
            $isWadir4 = true;
        }

        if ($isWadir4) {
            $wadir4Privilege = Privilege::firstOrCreate(
                ['nama' => 'Wakil Direktur IV'],
                [
                    'is_admin_panel' => true,
                    'deskripsi' => 'Pejabat Wakil Direktur IV Polinema',
                    'can_create' => true,
                    'can_read' => true,
                    'can_update' => true,
                    'can_delete' => true,
                ]
            );

            UserPrivilege::updateOrCreate(
                ['user_id' => $user->id],
                ['privilege_id' => $wadir4Privilege->id]
            );
        } else {
            // Pengguna Dosen / Pegawai biasa
            $currentPrivName = $user->userPrivilege?->privilege?->nama;
            // Jangan timpa privilege jika sudah diberi privilege khusus dari Super Admin (misal: Super Admin, Koor Magang)
            if (!$currentPrivName || in_array($currentPrivName, ['User biasa', 'Dosen'])) {
                $targetRoleName = ($namaPekerjaan === 'dosen' || empty($namaPekerjaan)) ? 'Dosen' : 'User biasa';
                $rolePrivilege = Privilege::firstOrCreate(
                    ['nama' => $targetRoleName],
                    [
                        'is_admin_panel' => false,
                        'deskripsi' => 'Pengguna ' . $targetRoleName . ' Polinema',
                        'can_create' => true,
                        'can_read' => true,
                        'can_update' => true,
                        'can_delete' => false,
                    ]
                );

                UserPrivilege::updateOrCreate(
                    ['user_id' => $user->id],
                    ['privilege_id' => $rolePrivilege->id]
                );
            }
        }

        return $user->fresh(['userPrivilege.privilege', 'userProgramStudi.programStudi', 'programStudis', 'jurusan']);
    }

    /**
     * Mengambil data pejabat Wakil Direktur IV dari API Kepegawaian
     */
    public function getPejabatWadir4(): ?array
    {
        return Cache::remember('polinema_pejabat_wadir4', 3600, function () {
            if (!$this->isConfigured()) {
                return null;
            }

            try {
                // Cari data pejabat Wadir 4 berdasarkan NIP resmi
                $pegawai = $this->getPegawaiDetail('198010102005011001', 'Rosa Andrie Asmara');
                if (!empty($pegawai)) {
                    return [
                        'nama' => $pegawai['biodata']['namaGelar'] ?? $pegawai['biodata']['namaGelar2'] ?? $pegawai['biodata']['namaPegawai'] ?? 'Prof. Dr. Eng. Rosa Andrie Asmara, S.T., M.T.',
                        'nip'  => $pegawai['nomor_identitas']['nipBaru'] ?? $pegawai['nomor_identitas']['nipLama'] ?? '198010102005011001',
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('getPejabatWadir4 error: ' . $e->getMessage());
            }

            return null;
        });
    }

    /**
     * Mengambil pohon hirarki Jurusan & Prodi dari API
     */
    public function getHirarkiUker(): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        try {
            $response = $this->client()->get("{$this->baseUrl}/uker/hirarki", [
                'sub_uker' => 1,
                'pimpinan' => 1,
            ]);

            if ($response->successful()) {
                $json = $response->json();
                if (($json['status'] ?? '') === 'success' && !empty($json['data'])) {
                    return (array) $json['data'];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('getHirarkiUker error: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Mengambil daftar Jurusan dari API
     */
    public function getJurusanList(): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        try {
            $response = $this->client()->get("{$this->baseUrl}/uker/jurusan", [
                'pimpinan' => 1,
            ]);

            if ($response->successful()) {
                $json = $response->json();
                if (($json['status'] ?? '') === 'success' && !empty($json['data'])) {
                    return (array) $json['data'];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('getJurusanList error: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Mengambil daftar Prodi dari API
     */
    public function getProdiList(): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        try {
            $response = $this->client()->get("{$this->baseUrl}/uker/prodi", [
                'pimpinan' => 1,
            ]);

            if ($response->successful()) {
                $json = $response->json();
                if (($json['status'] ?? '') === 'success' && !empty($json['data'])) {
                    return (array) $json['data'];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('getProdiList error: ' . $e->getMessage());
        }

        return [];
    }
}
