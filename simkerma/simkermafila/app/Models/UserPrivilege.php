<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPrivilege extends Model
{
    protected $table = 'user_privileges';

    protected $fillable = [
        'user_id',
        'privilege_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function privilege(): BelongsTo
    {
        return $this->belongsTo(Privilege::class);
    }
}