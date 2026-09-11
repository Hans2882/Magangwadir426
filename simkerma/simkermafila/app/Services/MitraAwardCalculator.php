<?php

namespace App\Services;

use App\Models\Kerjasama;
use App\Models\Mitra;
use App\Models\MitraAwardScore;
use App\Models\UsulanKerjasama;

class MitraAwardCalculator
{
    /**
     * Semua kriteria penilaian.
     */
    public const CRITERIA = [
        'dokumen_score',
        'kurikulum',
        'magang',
        'dosen_industri',
        'rekrutmen',
        'penelitian_cash',
        'penelitian_kind',
        'hilirisasi',
        'khalayak_pkm',
        'publikasi_bersama',
        'co_hosting',
        'pelatihan_sertifikasi',
        'kajian_tenaga_ahli',
        'hibah_alat',
        'reputasi',
        'perluasan_jejaring',
    ];

    /**
     * Label kriteria untuk kebutuhan UI.
     */
    public const CRITERIA_LABELS = [
        'dokumen_score' => 'Ketersediaan Dokumen Kerja Sama',
        'kurikulum' => 'Kurikulum',
        'magang' => 'Magang',
        'dosen_industri' => 'Dosen Industri',
        'rekrutmen' => 'Rekrutmen',
        'penelitian_cash' => 'Penelitian kerja sama dengan kontribusi in-cash',
        'penelitian_kind' => 'Penelitian kerja sama dengan kontribusi in-kind',
        'hilirisasi' => 'Hilirisasi hasil penelitian',
        'khalayak_pkm' => 'Khalayak sasaran PkM',
        'publikasi_bersama' => 'Publikasi bersama',
        'co_hosting' => 'Co-hosting pertemuan ilmiah',
        'pelatihan_sertifikasi' => 'Proyek pelatihan/sertifikasi',
        'kajian_tenaga_ahli' => 'Proyek kajian/tenaga ahli',
        'hibah_alat' => 'Hibah alat/sarana/beasiswa',
        'reputasi' => 'Reputasi',
        'perluasan_jejaring' => 'Perluasan jejaring',
    ];

    /**
     * Satuan setiap kriteria.
     */
    public const CRITERIA_UNITS = [
        'dokumen_score' => 'dok',
        'kurikulum' => 'kali workshop',
        'magang' => 'jumlah mahasiswa',
        'dosen_industri' => 'jumlah dosen',
        'rekrutmen' => 'jumlah alumni',
        'penelitian_cash' => 'Rp',
        'penelitian_kind' => 'setara Rp',
        'hilirisasi' => 'jumlah produk/jasa',
        'khalayak_pkm' => 'jumlah masyarakat',
        'publikasi_bersama' => 'jumlah artikel',
        'co_hosting' => 'kali pertemuan',
        'pelatihan_sertifikasi' => 'jumlah peserta',
        'kajian_tenaga_ahli' => 'Rp',
        'hibah_alat' => 'setara Rp',
        'reputasi' => 'Likert',
        'perluasan_jejaring' => 'Likert',
    ];

    /**
     * Default konfigurasi.
     *
     * Bobot disimpan sebagai desimal:
     *
     * 5%   = 0.05
     * 7.5% = 0.075
     * 10%  = 0.10
     */
    public const DEFAULT_CONFIG = [
        'bobot' => [

            'dokumen_score' => 0.05,

            'kurikulum' => 0.075,
            'magang' => 0.10,
            'dosen_industri' => 0.075,
            'rekrutmen' => 0.10,

            'penelitian_cash' => 0.10,
            'penelitian_kind' => 0.025,
            'hilirisasi' => 0.09,
            'khalayak_pkm' => 0.025,
            'publikasi_bersama' => 0.075,
            'co_hosting' => 0.015,

            'pelatihan_sertifikasi' => 0.06,
            'kajian_tenaga_ahli' => 0.06,
            'hibah_alat' => 0.05,

            'reputasi' => 0.05,
            'perluasan_jejaring' => 0.05,
        ],

        'skala' => [

            /**
             * Dokumen otomatis dihitung berdasarkan data Kerjasama.
             */
            'dokumen_score' => [
                'type' => 'document',
                'labels' => [
                    0 => 'Belum Memiliki Dokumen Kerja Sama',
                    1 => 'Sudah Melakukan Inisiasi Kerja Sama',
                    2 => 'IA',
                    3 => 'PKS',
                    4 => 'MoU',
                ],
            ],

            /**
             * Count.
             *
             * thresholds:
             *
             * 0 = score 0
             * >0 sampai 1 = score 1
             * >1 sampai 2 = score 2
             * >2 sampai 3 = score 3
             * >3 = score 4
             *
             * Karena nilai normal biasanya integer,
             * hasilnya menjadi 0,1,2,3,4.
             */
            'kurikulum' => [
    'type' => 'count',
    'thresholds' => [0, 1, 2, 3],
],

            'magang' => [
                'type' => 'count',
                'thresholds' => [0, 1, 2, 3],
            ],

            'dosen_industri' => [
                'type' => 'count',
                'thresholds' => [0, 1, 2, 3],
            ],

            'rekrutmen' => [
                'type' => 'count',
                'thresholds' => [0, 1, 2, 3],
            ],

            'hilirisasi' => [
                'type' => 'count',
                'thresholds' => [0, 1, 2, 3],
            ],

            'khalayak_pkm' => [
                'type' => 'count',
                'thresholds' => [0, 1, 2, 3],
            ],

            'publikasi_bersama' => [
                'type' => 'count',
                'thresholds' => [0, 1, 2, 3],
            ],

            'co_hosting' => [
                'type' => 'count',
                'thresholds' => [0, 1, 2, 3],
            ],

            'pelatihan_sertifikasi' => [
                'type' => 'count',
                'thresholds' => [0, 1, 2, 3],
            ],

            /**
             * Money.
             */
            'penelitian_cash' => [
                'type' => 'money',
                'thresholds' => [
                    0,
                    2_000_000,
                    5_000_000,
                    10_000_000,
                ],
            ],

            'penelitian_kind' => [
                'type' => 'money',
                'thresholds' => [
                    0,
                    4_000_000,
                    10_000_000,
                    20_000_000,
                ],
            ],

            'kajian_tenaga_ahli' => [
                'type' => 'money',
                'thresholds' => [
                    0,
                    20_000_000,
                    50_000_000,
                    100_000_000,
                ],
            ],

            'hibah_alat' => [
                'type' => 'money',
                'thresholds' => [
                    0,
                    20_000_000,
                    50_000_000,
                    100_000_000,
                ],
            ],

            /**
             * Likert.
             *
             * Nilai 0-4 langsung menjadi score.
             */
            'reputasi' => [
                'type' => 'likert',
                'thresholds' => [0, 1, 2, 3],
                'labels' => [
                    0 => 'Tidak Bereputasi',
                    1 => 'Bereputasi Rendah',
                    2 => 'Bereputasi Sedang',
                    3 => 'Bereputasi Tinggi',
                    4 => 'Bereputasi Sangat Tinggi',
                ],
            ],

            'perluasan_jejaring' => [
                'type' => 'likert',
                'thresholds' => [0, 1, 2, 3],
                'labels' => [
                    0 => 'Tidak Berpengaruh',
                    1 => 'Berpengaruh Sedikit',
                    2 => 'Berpengaruh Sedang',
                    3 => 'Berpengaruh Banyak',
                    4 => 'Berpengaruh Sangat Banyak',
                ],
            ],
        ],
    ];

    /**
     * Mengambil konfigurasi default.
     */
    public static function defaultConfig(): array
    {
        return self::DEFAULT_CONFIG;
    }

    /**
     * Label kriteria.
     */
    public static function criteriaLabels(): array
    {
        return self::CRITERIA_LABELS;
    }

    /**
     * Unit kriteria.
     */
    public static function criteriaUnits(): array
    {
        return self::CRITERIA_UNITS;
    }

    /**
     * Menghitung total score.
     */
    public function calculate(MitraAwardScore $score): float
    {
        $config = $this->getConfig($score);

        $weights = $config['bobot'] ?? [];
        $scale = $config['skala'] ?? [];

        $normalized = [];

        foreach (self::CRITERIA as $criterion) {

            /**
             * Dokumen dihitung otomatis.
             */
            if ($criterion === 'dokumen_score') {
                $value = (int) $score->dokumen_score;

                $normalized[$criterion] = $this->calculateCriterionScore(
                    $criterion,
                    $value,
                    $scale
                );

                continue;
            }

            $value = (float) ($score->{$criterion} ?? 0);

            $normalized[$criterion] = $this->calculateCriterionScore(
                $criterion,
                $value,
                $scale
            );
        }

        /**
         * Weighted score.
         *
         * Score maksimum = 4.
         *
         * Contoh:
         *
         * normalized = 4
         * weight     = 0.10
         *
         * kontribusi = 4 x 0.10 = 0.40
         *
         * total maksimum = 4.
         *
         * Kemudian dikali 25 sehingga maksimum = 100.
         */
        $weightedScore = 0;

        foreach ($weights as $criterion => $weight) {
            $weightedScore +=
                ($normalized[$criterion] ?? 0)
                * (float) $weight;
        }

        return round(
            min(100, $weightedScore * 25),
            4
        );
    }

    /**
     * Mengambil konfigurasi yang digunakan oleh score.
     */
    public function getConfig(MitraAwardScore $score): array
    {
        $period = $score->relationLoaded('period')
            ? $score->period
            : $score->period()->first();

        $customConfig = $period?->konfigurasi_penilaian ?? [];

        if (! is_array($customConfig)) {
            $customConfig = [];
        }

        return array_replace_recursive(
            self::DEFAULT_CONFIG,
            $customConfig
        );
    }

    /**
     * Menghitung score sebuah kriteria.
     */
    protected function calculateCriterionScore(
        string $criterion,
        float $value,
        array $scale
    ): int {
        $configuration = $scale[$criterion] ?? null;

        if (! $configuration) {
            return $this->countScore((int) $value);
        }

        $type = $configuration['type'] ?? 'count';

        return match ($type) {
            'money' => $this->thresholdScore(
                $value,
                $configuration['thresholds'] ?? []
            ),

            'count' => $this->thresholdScore(
                $value,
                $configuration['thresholds'] ?? [0, 1, 2, 3]
            ),

            'likert' => $this->thresholdScore(
                $value,
                $configuration['thresholds'] ?? [0, 1, 2, 3]
            ),

            'document' => $this->countScore((int) $value),

            default => $this->countScore((int) $value),
        };
    }

    /**
     * Score count standar 0-4.
     */
    public function countScore(int $value): int
    {
        return max(
            0,
            min(4, $value)
        );
    }

    /**
     * Menghitung score berdasarkan threshold.
     *
     * Contoh:
     *
     * thresholds:
     * [0, 1, 2, 3]
     *
     * hasil:
     *
     * <= 0  = 0
     * <= 1  = 1
     * <= 2  = 2
     * <= 3  = 3
     * > 3   = 4
     */
    public function thresholdScore(
        float $value,
        array $thresholds
    ): int {
        if (empty($thresholds)) {
            return 0;
        }

        $thresholds = array_values(
            array_map('floatval', $thresholds)
        );

        sort($thresholds);

        if ($value <= $thresholds[0]) {
            return 0;
        }

        foreach (
            array_slice($thresholds, 1) as $index => $threshold
        ) {
            if ($value <= $threshold) {
                return $index + 1;
            }
        }

        return 4;
    }

    /**
     * Alias lama agar kode lain tetap kompatibel.
     */
    public function cashScore(
        float $value,
        array $thresholds
    ): int {
        return $this->thresholdScore(
            $value,
            $thresholds
        );
    }

    /**
     * Score dokumen berdasarkan data kerjasama mitra.
     */
    public function getDocumentScore(?Mitra $mitra): int
    {
        if (! $mitra) {
            return 0;
        }

        $documentTypes = Kerjasama::query()
            ->where('mitra_id', $mitra->getKey())
            ->pluck('jenis_dokumen_id');

        /**
         * MoU
         */
        if ($documentTypes->contains(1)) {
            return 4;
        }

        /**
         * PKS / SPK
         */
        if (
            $documentTypes->contains(
                fn ($type): bool => in_array(
                    (int) $type,
                    [3, 5],
                    true
                )
            )
        ) {
            return 3;
        }

        /**
         * IA
         */
        if ($documentTypes->contains(4)) {
            return 2;
        }

        /**
         * Dokumen lain.
         */
        if ($documentTypes->isNotEmpty()) {
            return 1;
        }

        /**
         * Belum ada dokumen,
         * tetapi sudah ada usulan kerja sama.
         */
        return UsulanKerjasama::query()
            ->where('mitra_id', $mitra->getKey())
            ->exists()
            ? 1
            : 0;
    }

    /**
     * Validasi total bobot harus 100%.
     */
    public function validateWeights(array $weights): bool
    {
        $total = array_sum(
            array_map('floatval', $weights)
        );

        return abs($total - 1.0) < 0.0001;
    }

    /**
     * Total bobot dalam persen.
     */
    public function getWeightPercentage(
        array $weights
    ): float {
        return round(
            array_sum(
                array_map('floatval', $weights)
            ) * 100,
            4
        );
    }
}