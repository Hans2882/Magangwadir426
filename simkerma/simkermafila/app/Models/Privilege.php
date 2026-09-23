<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Privilege extends Model
{
    protected $table = 'privileges';

    protected $fillable = [
        'nama',
        'is_admin_panel',
        'deskripsi',
        'can_create',
        'can_read',
        'can_update',
        'can_delete',
    ];
}