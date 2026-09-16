<?php

namespace App\Models;

use Filament\Actions\Action;
use App\Services\MitraAwardCalculator;
use App\Services\MitraAwardRanking;
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
        /*
         * Saat membuat periode baru, jika belum ada
         * konfigurasi penilaian maka gunakan konfigurasi default.
         */
        static::creating(function (self $period): void {
            if (empty($period->konfigurasi_penilaian)) {
                $period->konfigurasi_penilaian =
                    MitraAwardCalculator::defaultConfig();
            }
        });

        /*
         * Saat periode disimpan:
         * - Pastikan konfigurasi selalu tersedia.
         * - Jika periode dibuat aktif, nonaktifkan periode lainnya.
         */
        static::saving(function (self $period): void {
            if (empty($period->konfigurasi_penilaian)) {
                $period->konfigurasi_penilaian =
                    MitraAwardCalculator::defaultConfig();
            }

            if ($period->is_active) {
                static::query()
                    ->when(
                        $period->exists,
                        fn ($query) => $query->whereKeyNot(
                            $period->getKey()
                        )
                    )
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                    ]);
            }
        });

        /*
         * Setelah konfigurasi penilaian berubah,
         * hitung ulang seluruh score pada periode tersebut.
         */
        static::saved(function (self $period): void {
            if (
                $period->wasChanged('konfigurasi_penilaian')
                && $period->exists
            ) {
                $calculator = app(MitraAwardCalculator::class);

                $scores = $period->scores()
                    ->with('mitra')
                    ->get();

                foreach ($scores as $score) {
                    /*
                     * Hitung ulang dokumen score.
                     */
                    $score->dokumen_score =
                        $calculator->getDocumentScore(
                            $score->mitra
                        );

                    /*
                     * Pastikan calculator menggunakan konfigurasi
                     * periode yang baru.
                     */
                    $score->setRelation('period', $period);

                    /*
                     * Hitung ulang total score.
                     */
                    $score->total_score =
                        $calculator->calculate($score);

                    /*
                     * updateQuietly agar event saving/saved
                     * MitraAwardScore tidak memanggil ranking
                     * berulang kali untuk setiap score.
                     */
                    $score->updateQuietly([
                        'dokumen_score' => $score->dokumen_score,
                        'total_score' => $score->total_score,
                    ]);
                }

                /*
                 * Ranking cukup dihitung SATU KALI
                 * setelah seluruh score selesai diperbarui.
                 */
                app(MitraAwardRanking::class)
                    ->recalculate($period->getKey());
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