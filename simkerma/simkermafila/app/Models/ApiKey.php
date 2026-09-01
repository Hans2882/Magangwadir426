<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKey extends Model
{
    protected $table = 'api_keys';

    protected $fillable = [
        'user_id',
        'name',
        'key',
        'is_active',
        'allowed_endpoints',
        'ip_whitelist',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allowed_endpoints' => 'array',
        'ip_whitelist' => 'array',
        'last_used_at' => 'datetime',
    ];

    /**
     * Generate API Key baru.
     */
    public static function generateKey(): string
    {
        return 'sk_' . bin2hex(random_bytes(32));
    }

    /**
     * Relasi ke pemilik API Key.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope API Key aktif.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Periksa apakah endpoint boleh diakses.
     */
    public function canAccessEndpoint(string $endpoint): bool
    {
        if (empty($this->allowed_endpoints)) {
            return true;
        }

        return in_array('*', $this->allowed_endpoints, true)
            || in_array($endpoint, $this->allowed_endpoints, true);
    }

    /**
     * Periksa IP whitelist.
     */
    public function isIpAllowed(string $ip): bool
    {
        if (empty($this->ip_whitelist)) {
            return true;
        }

        return in_array($ip, $this->ip_whitelist, true);
    }
}