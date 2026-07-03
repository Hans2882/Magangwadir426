<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterMitraIku extends Model
{
    protected $table = 'master_mitra_iku';
    public $timestamps = false;
    
    protected $fillable = [
        'kategori',
        'bobot',
    ];
}
