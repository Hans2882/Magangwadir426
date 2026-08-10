<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterKota extends Model
{
    protected $table = 'master_kota';

    protected $fillable = [
        'provinsi_id',
        'nama_kota',
    ];

    public function provinsi()
    {
        return $this->belongsTo(MasterProvinsi::class, 'provinsi_id');
    }

    public function mitras()
    {
        return $this->hasMany(Mitra::class, 'kota_id');
    }
}
