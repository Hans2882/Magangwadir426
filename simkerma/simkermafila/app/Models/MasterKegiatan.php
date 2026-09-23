<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterKegiatan extends Model
{
    protected $table = 'master_kegiatan';
    protected $fillable = [
        'bidang_kerjasama',
        'bidang_unit',
        'jenis_dokumen',
    ];
}
