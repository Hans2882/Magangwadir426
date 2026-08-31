<?php

namespace App\Services;

use App\Models\Kerjasama;
use App\Models\Mitra;
use App\Models\MitraAwardScore;
use App\Models\UsulanKerjasama;

class MitraAwardCalculator
{
    public const WEIGHTS = [
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
    ];

    public function calculate(MitraAwardScore $score): float
    {
        $normalized = [
            'dokumen_score' => (int) $score->dokumen_score,
            'kurikulum' => $this->countScore((int) $score->kurikulum),
            'magang' => $this->countScore((int) $score->magang),
            'dosen_industri' => $this->countScore((int) $score->dosen_industri),
            'rekrutmen' => $this->countScore((int) $score->rekrutmen),
            'penelitian_cash' => $this->cashScore((float) $score->penelitian_cash, [0, 2000000, 5000000, 10000000]),
            'penelitian_kind' => $this->cashScore((float) $score->penelitian_kind, [0, 4000000, 10000000, 20000000]),
            'hilirisasi' => $this->countScore((int) $score->hilirisasi),
            'khalayak_pkm' => $this->countScore((int) $score->khalayak_pkm),
            'publikasi_bersama' => $this->countScore((int) $score->publikasi_bersama),
            'co_hosting' => $this->countScore((int) $score->co_hosting),
            'pelatihan_sertifikasi' => $this->countScore((int) $score->pelatihan_sertifikasi),
            'kajian_tenaga_ahli' => $this->cashScore((float) $score->kajian_tenaga_ahli, [0, 20000000, 50000000, 100000000]),
            'hibah_alat' => $this->cashScore((float) $score->hibah_alat, [0, 20000000, 50000000, 100000000]),
            'reputasi' => $this->countScore((int) $score->reputasi),
            'perluasan_jejaring' => $this->countScore((int) $score->perluasan_jejaring),
        ];

        return round(min(100, array_sum(array_map(
            fn (string $criterion): float => $normalized[$criterion] * self::WEIGHTS[$criterion],
            array_keys(self::WEIGHTS),
        )) * 25), 4);
    }

    public function countScore(int $value): int
    {
        return max(0, min(4, $value));
    }

    public function cashScore(float $value, array $thresholds): int
    {
        if ($value <= $thresholds[0]) {
            return 0;
        }

        foreach (array_slice($thresholds, 1) as $score => $threshold) {
            if ($value <= $threshold) {
                return $score + 1;
            }
        }

        return 4;
    }

    public function getDocumentScore(?Mitra $mitra): int
    {
        if (! $mitra) {
            return 0;
        }

        $documentTypes = Kerjasama::query()
            ->where('mitra_id', $mitra->getKey())
            ->pluck('jenis_dokumen_id');

        if ($documentTypes->contains(1)) {
            return 4;
        }

        if ($documentTypes->contains(fn ($type): bool => in_array((int) $type, [3, 5], true))) {
            return 3;
        }

        if ($documentTypes->contains(4)) {
            return 2;
        }

        if ($documentTypes->isNotEmpty()) {
            return 1;
        }

        return UsulanKerjasama::query()
            ->where('mitra_id', $mitra->getKey())
            ->exists() ? 1 : 0;
    }
}
