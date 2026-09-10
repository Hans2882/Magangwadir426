<?php

namespace App\Models;

use App\Services\MitraAwardCalculator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MitraAwardPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'tahun',
        'tanggal_mulai',
        'tanggal_selesai',
        'is_active',
        'konfigurasi_penilaian',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'is_active' => 'boolean',
        'konfigurasi_penilaian' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $period): void {
            if (empty($period->konfigurasi_penilaian)) {
                $period->konfigurasi_penilaian =
                    MitraAwardCalculator::defaultConfig();
            }
        });

        static::saving(function (self $period): void {
            if (empty($period->konfigurasi_penilaian)) {
                $period->konfigurasi_penilaian =
                    MitraAwardCalculator::defaultConfig();
            }

            if ($period->is_active) {
                static::query()
                    ->when(
                        $period->exists,
                        fn ($query) => $query->whereKeyNot($period->getKey())
                    )
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                    ]);
            }
        });
    }

    public function scores(): HasMany
    {
        return $this->hasMany(
            MitraAwardScore::class,
            'mitra_award_period_id'
        );
    }
}