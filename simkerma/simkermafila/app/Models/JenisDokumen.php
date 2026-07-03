<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JenisDokumen extends Model
{
    protected $table = 'master_jenis_dokumen';

    protected $fillable = ['nama'];

    public $timestamps = false;
}
