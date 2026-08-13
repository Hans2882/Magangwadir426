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

    public function getNamaMitraDisplayAttribute(): string
    {
        return $this->nama_mitra ?? '';
    }

    public function kerjasamas(): HasMany
{
    return $this->hasMany(Kerjasama::class, 'mitra_id');
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