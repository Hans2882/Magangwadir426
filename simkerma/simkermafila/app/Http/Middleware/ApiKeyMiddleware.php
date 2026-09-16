<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $apiKey = $request->header('X-API-KEY')
            ?? $request->query('api_key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API Key diperlukan.',
            ], 401);
        }

        $validInDatabase = ApiKey::query()
            ->where('key', $apiKey)
            ->where('is_active', true)
            ->exists();

        $configuredKey = config('services.web_api.key');
        $validInConfig = is_string($configuredKey)
            && $configuredKey !== ''
            && hash_equals($configuredKey, $apiKey);

        if (!$validInDatabase && !$validInConfig) {
            return response()->json([
                'success' => false,
                'message' => 'API Key tidak valid.',
            ], 401);
        }

        return $next($request);
    }
}