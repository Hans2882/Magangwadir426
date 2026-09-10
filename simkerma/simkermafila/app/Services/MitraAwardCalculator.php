<?php

namespace App\Services;

use App\Models\Kerjasama;
use App\Models\Mitra;
use App\Models\MitraAwardScore;
use App\Models\UsulanKerjasama;

class MitraAwardCalculator
{
    /**
     * Konfigurasi default penilaian.
     *
     * Bobot menggunakan format desimal:
     *
     * 5%   = 0.05
     * 7.5% = 0.075
     * 10%  = 0.10
     *
     * Skala:
     * - count  = berdasarkan jumlah/nilai 0-4
     * - money  = berdasarkan threshold nominal
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
            /*
             * Count score
             *
             * Nilai langsung dibatasi 0 - 4.
             */
            'kurikulum' => [
                'type' => 'count',
                'values' => [0, 1, 2, 3, 4],
            ],

            'magang' => [
                'type' => 'count',
                'values' => [0, 1, 2, 3, 4],
            ],

            'dosen_industri' => [
                'type' => 'count',
                'values' => [0, 1, 2, 3, 4],
            ],

            'rekrutmen' => [
                'type' => 'count',
                'values' => [0, 1, 2, 3, 4],
            ],

            'hilirisasi' => [
                'type' => 'count',
                'values' => [0, 1, 2, 3, 4],
            ],

            'khalayak_pkm' => [
                'type' => 'count',
                'values' => [0, 1, 2, 3, 4],
            ],

            'publikasi_bersama' => [
                'type' => 'count',
                'values' => [0, 1, 2, 3, 4],
            ],

            'co_hosting' => [
                'type' => 'count',
                'values' => [0, 1, 2, 3, 4],
            ],

            'pelatihan_sertifikasi' => [
                'type' => 'count',
                'values' => [0, 1, 2, 3, 4],
            ],

            'reputasi' => [
                'type' => 'count',
                'values' => [0, 1, 2, 3, 4],
            ],

            'perluasan_jejaring' => [
                'type' => 'count',
                'values' => [0, 1, 2, 3, 4],
            ],

            /*
             * Money score
             *
             * Contoh:
             *
             * 0              = score 0
             * > 0 - 2 juta   = score 1
             * > 2 - 5 juta   = score 2
             * > 5 - 10 juta  = score 3
             * > 10 juta      = score 4
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
     * Menghitung total score mitra berdasarkan periode.
     */
    public function calculate(MitraAwardScore $score): float
    {
        $config = $this->getConfig($score);

        $weights = $config['bobot'] ?? [];
        $scale = $config['skala'] ?? [];

        $normalized = [
            'dokumen_score' => $this->countScore(
                (int) $score->dokumen_score
            ),

            'kurikulum' => $this->calculateCriterionScore(
                'kurikulum',
                (float) $score->kurikulum,
                $scale
            ),

            'magang' => $this->calculateCriterionScore(
                'magang',
                (float) $score->magang,
                $scale
            ),

            'dosen_industri' => $this->calculateCriterionScore(
                'dosen_industri',
                (float) $score->dosen_industri,
                $scale
            ),

            'rekrutmen' => $this->calculateCriterionScore(
                'rekrutmen',
                (float) $score->rekrutmen,
                $scale
            ),

            'penelitian_cash' => $this->calculateCriterionScore(
                'penelitian_cash',
                (float) $score->penelitian_cash,
                $scale
            ),

            'penelitian_kind' => $this->calculateCriterionScore(
                'penelitian_kind',
                (float) $score->penelitian_kind,
                $scale
            ),

            'hilirisasi' => $this->calculateCriterionScore(
                'hilirisasi',
                (float) $score->hilirisasi,
                $scale
            ),

            'khalayak_pkm' => $this->calculateCriterionScore(
                'khalayak_pkm',
                (float) $score->khalayak_pkm,
                $scale
            ),

            'publikasi_bersama' => $this->calculateCriterionScore(
                'publikasi_bersama',
                (float) $score->publikasi_bersama,
                $scale
            ),

            'co_hosting' => $this->calculateCriterionScore(
                'co_hosting',
                (float) $score->co_hosting,
                $scale
            ),

            'pelatihan_sertifikasi' => $this->calculateCriterionScore(
                'pelatihan_sertifikasi',
                (float) $score->pelatihan_sertifikasi,
                $scale
            ),

            'kajian_tenaga_ahli' => $this->calculateCriterionScore(
                'kajian_tenaga_ahli',
                (float) $score->kajian_tenaga_ahli,
                $scale
            ),

            'hibah_alat' => $this->calculateCriterionScore(
                'hibah_alat',
                (float) $score->hibah_alat,
                $scale
            ),

            'reputasi' => $this->calculateCriterionScore(
                'reputasi',
                (float) $score->reputasi,
                $scale
            ),

            'perluasan_jejaring' => $this->calculateCriterionScore(
                'perluasan_jejaring',
                (float) $score->perluasan_jejaring,
                $scale
            ),
        ];

        /*
         * Hitung weighted score.
         *
         * normalized maksimal = 4
         * total weighted maksimal = 4
         *
         * Kemudian dikali 25 agar:
         *
         * 4 x 25 = 100
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
     *
     * Konfigurasi periode akan digabung dengan default.
     *
     * Ini membuat konfigurasi lama tetap aman apabila
     * ada bagian konfigurasi yang belum tersedia.
     */
    public function getConfig(MitraAwardScore $score): array
    {
        $period = $score->relationLoaded('period')
            ? $score->period
            : $score->period()->first();

        $customConfig = $period?->konfigurasi_penilaian ?? [];

        return array_replace_recursive(
            self::DEFAULT_CONFIG,
            $customConfig
        );
    }

    /**
     * Menghitung score sebuah kriteria berdasarkan konfigurasi.
     */
    protected function calculateCriterionScore(
        string $criterion,
        float $value,
        array $scale
    ): int {
        $configuration = $scale[$criterion] ?? null;

        /*
         * Jika konfigurasi tidak ditemukan,
         * gunakan perilaku count 0-4.
         */
        if (! $configuration) {
            return $this->countScore((int) $value);
        }

        $type = $configuration['type'] ?? 'count';

        if ($type === 'money') {
            return $this->cashScore(
                $value,
                $configuration['thresholds'] ?? [0, 1, 2, 3]
            );
        }

        return $this->countScore((int) $value);
    }

    /**
     * Score untuk kriteria berbasis jumlah.
     */
    public function countScore(int $value): int
    {
        return max(0, min(4, $value));
    }

    /**
     * Score untuk kriteria berbasis nominal.
     *
     * Contoh:
     *
     * thresholds:
     * [
     *     0,
     *     2_000_000,
     *     5_000_000,
     *     10_000_000,
     * ]
     *
     * Hasil:
     *
     * <= 0                  => 0
     * > 0 sampai <= 2 juta  => 1
     * > 2 sampai <= 5 juta  => 2
     * > 5 sampai <= 10 juta => 3
     * > 10 juta              => 4
     */
    public function cashScore(float $value, array $thresholds): int
    {
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
     * Mengambil score dokumen berdasarkan data kerjasama mitra.
     */
    public function getDocumentScore(?Mitra $mitra): int
    {
        if (! $mitra) {
            return 0;
        }

        $documentTypes = Kerjasama::query()
            ->where('mitra_id', $mitra->getKey())
            ->pluck('jenis_dokumen_id');

        /*
         * MoU
         */
        if ($documentTypes->contains(1)) {
            return 4;
        }

        /*
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

        /*
         * IA
         */
        if ($documentTypes->contains(4)) {
            return 2;
        }

        /*
         * Dokumen lain
         */
        if ($documentTypes->isNotEmpty()) {
            return 1;
        }

        /*
         * Belum ada dokumen, tetapi ada usulan kerjasama.
         */
        return UsulanKerjasama::query()
            ->where('mitra_id', $mitra->getKey())
            ->exists()
            ? 1
            : 0;
    }

    /**
     * Validasi total bobot.
     *
     * Total harus 1.0 = 100%.
     */
    public function validateWeights(array $weights): bool
    {
        $total = array_sum(
            array_map('floatval', $weights)
        );

        return abs($total - 1.0) < 0.0001;
    }

    /**
     * Mengembalikan total bobot dalam persen.
     */
    public function getWeightPercentage(array $weights): float
    {
        return round(
            array_sum(
                array_map('floatval', $weights)
            ) * 100,
            4
        );
    }
}