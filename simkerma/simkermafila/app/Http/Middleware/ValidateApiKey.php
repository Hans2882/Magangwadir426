<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Ambil API Key
        |--------------------------------------------------------------------------
        | Prioritas:
        | - Authorization: Bearer {API_KEY}
        | - X-API-Key: {API_KEY}
        */

        $key = $request->bearerToken()
    ?? $request->header('X-API-Key')
    ?? $request->query('api_key');

        if (blank($key)) {
            return response()->json([
                'success' => false,
                'message' => 'API Key tidak ditemukan.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Validasi API Key
        |--------------------------------------------------------------------------
        */

        $apiKey = ApiKey::active()
            ->where('key', $key)
            ->first();

        if (! $apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API Key tidak valid atau tidak aktif.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Cek IP Whitelist
        |--------------------------------------------------------------------------
        */

        $ipAddress = $request->ip();

        if (! $apiKey->isIpAllowed($ipAddress)) {
            return response()->json([
                'success' => false,
                'message' => 'IP Address tidak diizinkan.',
            ], Response::HTTP_FORBIDDEN);
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Cek Endpoint / Permission
        |--------------------------------------------------------------------------
        */

        $endpoint = $request->route()?->getName();

        if (! $endpoint) {
            return response()->json([
                'success' => false,
                'message' => 'Nama endpoint tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        if (! $apiKey->canAccessEndpoint($endpoint)) {
            return response()->json([
                'success' => false,
                'message' => 'API Key tidak memiliki akses ke endpoint ini.',
            ], Response::HTTP_FORBIDDEN);
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Update waktu terakhir digunakan
        |--------------------------------------------------------------------------
        */

        $apiKey->update([
            'last_used_at' => now(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | 6. Simpan ApiKey ke Request
        |--------------------------------------------------------------------------
        */

        $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }
}
