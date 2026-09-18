<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermintaanKerjasama extends Model
{
    use HasFactory;

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
        'status_permintaan',
        'user_id',
        'keterangan_tolak',
        'nama_pengusul'
    ];

    public function kategori()
    {
        return $this->belongsTo(MasterMitraIku::class, 'kategori_id');
    }

    public function negara()
    {
        return $this->belongsTo(Negara::class, 'negara_id');
    }

    public function provinsiModel()
    {
        return $this->belongsTo(MasterProvinsi::class, 'provinsi_id');
    }

    public function pengusul()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
