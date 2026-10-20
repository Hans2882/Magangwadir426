<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MasterProgramStudi extends Model
{
    use HasFactory;

    protected $table = 'master_program_studi';
    public $timestamps = false;

    protected $fillable = [
        'jurusan_id',
        'nama_prodi',
        'nama_prodi_eng',
        'integrasi_prodi',
    ];

    public function jurusan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MasterJurusan::class, 'jurusan_id');
    }
}