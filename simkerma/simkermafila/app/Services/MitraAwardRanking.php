<?php

namespace App\Services;

use App\Models\MitraAwardScore;

class MitraAwardRanking
{
    public function recalculate(int $periodId): void
    {
        // Resolve equal scores deterministically: document, magang, rekrutmen, then name.
        $scores = MitraAwardScore::query()
            ->with('mitra')
            ->where('mitra_award_period_id', $periodId)
            ->get()
            ->sort(function (MitraAwardScore $left, MitraAwardScore $right): int {
                return [$right->total_score, $right->dokumen_score, $right->magang, $right->rekrutmen, $left->mitra->nama_mitra]
                    <=> [$left->total_score, $left->dokumen_score, $left->magang, $left->rekrutmen, $right->mitra->nama_mitra];
            })
            ->values();

        foreach ($scores as $index => $score) {
            $score->newQuery()->whereKey($score->getKey())->update(['ranking' => $index + 1]);
        }
    }
}
