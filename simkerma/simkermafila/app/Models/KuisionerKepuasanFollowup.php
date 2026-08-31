<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KuisionerKepuasanFollowup extends Model
{
    protected $table = 'kuisioner_kepuasan_followup';

    protected $fillable = [
        'kuisioner_kepuasan_id',
        'catatan',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function kuisioner(): BelongsTo
    {
        return $this->belongsTo(KuisionerKepuasan::class, 'kuisioner_kepuasan_id');
    }
}
