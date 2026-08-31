<?php

namespace App\Models;

use App\Services\MitraAwardCalculator;
use App\Services\MitraAwardRanking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MitraAwardScore extends Model
{
    protected $fillable = [
        'mitra_award_period_id',
        'mitra_id',
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
        'total_score',
        'ranking',
    ];

    protected $casts = [
        'mitra_award_period_id' => 'integer',
        'mitra_id' => 'integer',
        'dokumen_score' => 'integer',
        'kurikulum' => 'integer',
        'magang' => 'integer',
        'dosen_industri' => 'integer',
        'rekrutmen' => 'integer',
        'penelitian_cash' => 'float',
        'penelitian_kind' => 'float',
        'hilirisasi' => 'integer',
        'khalayak_pkm' => 'integer',
        'publikasi_bersama' => 'integer',
        'co_hosting' => 'integer',
        'pelatihan_sertifikasi' => 'integer',
        'kajian_tenaga_ahli' => 'float',
        'hibah_alat' => 'float',
        'reputasi' => 'integer',
        'perluasan_jejaring' => 'integer',
        'total_score' => 'float',
        'ranking' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $score): void {
            $calculator = app(MitraAwardCalculator::class);
            $score->dokumen_score = $calculator->getDocumentScore($score->mitra()->first());
            $score->total_score = $calculator->calculate($score);
        });

        static::saved(function (self $score): void {
            app(MitraAwardRanking::class)->recalculate($score->mitra_award_period_id);
        });

        static::deleted(function (self $score): void {
            app(MitraAwardRanking::class)->recalculate($score->mitra_award_period_id);
        });
    }

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class, 'mitra_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(MitraAwardPeriod::class, 'mitra_award_period_id');
    }
}
