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
        'nama_prodi',
        'nama_prodi_eng',
    ];
}