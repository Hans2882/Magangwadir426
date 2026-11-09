<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mitra extends Model
{
    protected $table = 'mitra';

    public $timestamps = false;

    protected $fillable = [
        'nama_mitra',
        'kategori_id',
        'negara_id',
        'qs_rank',
        'telepon',
        'email',
        'alamat',
        'kota',
        'provinsi',
        'provinsi_id',
        'kota_id',
        'pic',
    ];

    // Expose commonly used aliases for Filament
    protected $appends = ['nama_mitra_display'];

    protected static function booted(): void
    {
        static::deleting(function (Mitra $mitra) {
            $details = $mitra->getRelatedDataSummary();
            if (!empty($details)) {
                \Filament\Notifications\Notification::make()
                    ->title('Tidak Dapat Menghapus Mitra')
                    ->body("Mitra '{$mitra->nama_mitra}' tidak dapat dihapus karena masih terikat dengan " . implode(', ', $details) . ". Silakan hapus atau alihkan data terkait terlebih dahulu.")
                    ->danger()
                    ->persistent()
                    ->send();

                return false;
            }
        });
    }

    public function getNamaMitraDisplayAttribute(): string
    {
        return $this->nama_mitra ?? '';
    }

    public function kerjasamas(): HasMany
    {
        return $this->hasMany(Kerjasama::class, 'mitra_id');
    }

    public function usulanKerjasamas(): HasMany
    {
        return $this->hasMany(UsulanKerjasama::class, 'mitra_id');
    }

    public function awardScores(): HasMany
    {
        return $this->hasMany(MitraAwardScore::class, 'mitra_id');
    }

    public function getRelatedDataSummary(): array
    {
        $details = [];
        $kerjasamaCount = $this->kerjasamas()->count();
        if ($kerjasamaCount > 0) {
            $details[] = "{$kerjasamaCount} dokumen kerjasama";
        }

        $usulanCount = $this->usulanKerjasamas()->count();
        if ($usulanCount > 0) {
            $details[] = "{$usulanCount} usulan kerjasama";
        }

        $awardCount = $this->awardScores()->count();
        if ($awardCount > 0) {
            $details[] = "{$awardCount} penilaian mitra award";
        }

        return $details;
    }

    public function negara(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Negara::class, 'negara_id');
    }

    public function kategori(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MasterMitraIku::class, 'kategori_id');
    }

    public function provinsiModel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MasterProvinsi::class, 'provinsi_id');
    }

    public function kotaModel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MasterKota::class, 'kota_id');
    }
}
