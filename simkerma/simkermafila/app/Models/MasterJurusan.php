<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterJurusan extends Model
{
    public $timestamps = false;
    protected $fillable = ['nama_jurusan', 'nama_jurusan_eng'];

    public function prodis()
    {
        return $this->hasMany(MasterProgramStudi::class, 'jurusan_id');
    }
}
