<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Kerjasama extends Model
{
    protected $table = 'kerjasama';

    protected $fillable = [
        'mitra_id',
        'jenis',
        'judul',
        'nomor_dokumen',
        'tahun',
        'tanggal_awal',
        'tanggal_akhir',
        'jenis_dokumen_id',
        'bidang_id',
        'link_dokumen',
        'link_perbaikan',
        'bukti_kegiatan',
    ];

    protected $casts = [
        'tanggal_awal' => \App\Casts\IndonesianDateCast::class,
        'tanggal_akhir' => \App\Casts\IndonesianDateCast::class,
    ];

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class, 'mitra_id');
    }

    public function jenisDokumen(): BelongsTo
    {
        return $this->belongsTo(JenisDokumen::class, 'jenis_dokumen_id');
    }

    // Accessor to mimic the old status field based on dates
    public function getStatusAttribute(): string
    {
        if (!$this->tanggal_akhir) {
            return '-';
        }

        return Carbon::now()->startOfDay()->lte($this->tanggal_akhir) ? 'AKTIF' : 'HABIS';
    }
}
