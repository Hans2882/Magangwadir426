<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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
     * Mengambil data pejabat Wakil Direktur IV dari API Kepegawaian
     */
    public function getPejabatWadir4(): ?array
    {
        return Cache::remember('polinema_pejabat_wadir4', 3600, function () {
            if (!$this->isConfigured()) {
                return null;
            }

            try {
                $response = $this->client()->post("{$this->baseUrl}/pegawai/index", [
                    'search' => 'Wakil Direktur',
                    'limit' => 20,
                    'start' => 0,
                    'riwayat' => ['jabstruk'],
                ]);

                if ($response->successful()) {
                    $json = $response->json();
                    if (($json['status'] ?? '') === 'success' && !empty($json['data'])) {
                        $pegawais = is_array($json['data']) ? $json['data'] : [];
                        foreach ($pegawais as $p) {
                            $jabatan = $p['jabatan'] ?? $p['jabstruk'] ?? '';
                            $jabText = is_array($jabatan) ? json_encode($jabatan) : (string) $jabatan;

                            if (stripos($jabText, 'Wakil Direktur IV') !== false || stripos($jabText, 'Wakil Direktur 4') !== false || stripos($jabText, 'Bidang IV') !== false) {
                                return [
                                    'nama' => $p['namaGelar2'] ?? $p['namaGelar'] ?? $p['namaPegawai'] ?? '',
                                    'nip' => $p['nipBaru'] ?? $p['nipLama'] ?? $p['nip'] ?? '',
                                ];
                            }
                        }
                    }
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
