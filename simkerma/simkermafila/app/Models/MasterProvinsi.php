<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterProvinsi extends Model
{
    protected $table = 'master_provinsi';

    protected $fillable = [
        'nama_provinsi',
    ];

    public function kotas()
    {
        return $this->hasMany(MasterKota::class, 'provinsi_id');
    }

    public function mitras()
    {
        return $this->hasMany(Mitra::class, 'provinsi_id');
    }
}
