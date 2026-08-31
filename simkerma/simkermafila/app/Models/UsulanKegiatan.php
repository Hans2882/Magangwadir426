<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsulanKegiatan extends Model
{
    protected $fillable = [
        'usulan_kerjasama_id',
        'master_kegiatan_id',
    ];
}
