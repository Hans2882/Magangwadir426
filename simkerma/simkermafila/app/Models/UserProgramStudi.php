<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProgramStudi extends Model
{
    protected $table = 'user_program_studi';

    protected $fillable = [
        'user_id',
        'program_studi_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(MasterProgramStudi::class, 'program_studi_id');
    }
}