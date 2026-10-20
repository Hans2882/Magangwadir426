<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterJurusan extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'nama_jurusan',
        'nama_jurusan_eng',
        'integrasi_jurusan',
        'nama_pimpinan',
        'nip_pimpinan',
    ];

    public function prodis()
    {
        return $this->hasMany(MasterProgramStudi::class, 'jurusan_id');
    }
}
