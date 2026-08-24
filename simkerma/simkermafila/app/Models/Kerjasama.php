<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Kerjasama extends Model
{
    protected $table = 'kerjasama';

    protected static function booted()
    {
        static::saving(function ($model) {
            if ($model->tanggal_awal) {
                $model->tahun = \Carbon\Carbon::parse($model->tanggal_awal)->year;
            }
        });
    }

    protected $fillable = [
        'parent_id',
        'mitra_id',
        'provinsi_id',
        'kota_id',
        'jenis',
        'judul',
        'nomor_dokumen',
        'tahun',
        'tanggal_awal',
        'tanggal_akhir',
        'jenis_dokumen_id',
        'bidang_id',
        'link_dokumen',
        'topdown',
        'link_perbaikan',
        'bukti_kegiatan',
        'link_laporan_kegiatan',
        'pks_id',
        'status_workflow',
        'jenis_pengajuan',
        'is_mitra_anonim',
    ];

    protected $casts = [
        'tanggal_awal' => 'date',
        'tanggal_akhir' => 'date',
        'is_mitra_anonim' => 'boolean',
    ];

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class, 'mitra_id');
    }

    public function provinsi(): BelongsTo
    {
        return $this->belongsTo(MasterProvinsi::class, 'provinsi_id');
    }

    public function kota(): BelongsTo
    {
        return $this->belongsTo(MasterKota::class, 'kota_id');
    }

    public function jenisDokumen(): BelongsTo
    {
        return $this->belongsTo(JenisDokumen::class, 'jenis_dokumen_id');
    }

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(MasterKegiatan::class, 'bidang_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Kerjasama::class, 'parent_id');
    }

    public function pks(): BelongsTo
    {
        return $this->belongsTo(Kerjasama::class, 'pks_id');
    }

    public function prodis(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(MasterProgramStudi::class, 'kerjasama_prodi', 'kerjasama_id', 'prodi_id');
    }

    public function jurusans(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(MasterJurusan::class, 'kerjasama_jurusan', 'kerjasama_id', 'jurusan_id');
    }

    public function getProdiDisplayNameAttribute(): string
    {
        $prodis = $this->relationLoaded('prodis')
            ? $this->prodis
            : $this->prodis()->get();

        if ($prodis->isEmpty()) {
            return '-';
        }

        $names = $prodis->pluck('nama_prodi')
            ->filter(fn ($name) => !empty($name))
            ->unique()
            ->values();

        return $names->isEmpty() ? '-' : $names->first();
    }

    // Accessor to mimic the old status field based on dates
    public function getStatusAttribute()
{
    if (!$this->tanggal_akhir) {
        return 'AKTIF';
    }

    $tanggalAkhir = Carbon::parse($this->tanggal_akhir)->startOfDay();
    $hariIni = now()->startOfDay();

    if ($tanggalAkhir->lt($hariIni)) {
        return 'BERAKHIR';
    }

    if ($tanggalAkhir->lte($hariIni->copy()->addMonth())) {
        return 'AKAN BERAKHIR';
    }

    return 'AKTIF';
}

    public function children(): HasMany
    {
        return $this->hasMany(Kerjasama::class, 'parent_id');
    }

    public function getPublicMitraNameAttribute(): string
    {
        $realName = $this->mitra ? $this->mitra->nama_mitra : '-';
        if (!\Illuminate\Support\Facades\Auth::check() && $this->is_mitra_anonim) {
            return 'PT. XYZ';
        }
        return $realName;
    }
}