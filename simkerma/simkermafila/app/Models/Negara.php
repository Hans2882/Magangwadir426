<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Negara extends Model
{
    protected $table = 'master_negara';

    public $timestamps = false;

    protected $fillable = ['nama_negara'];

    public function mitraLuarNegeri(): HasMany
    {
        return $this->hasMany(Mitra::class, 'negara_id');
    }
}
