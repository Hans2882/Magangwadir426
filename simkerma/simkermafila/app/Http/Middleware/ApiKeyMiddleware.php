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
        $apiKey = $request->header('X-API-KEY');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API Key diperlukan.',
            ], 401);
        }

        $valid = ApiKey::query()
            ->where('key', $apiKey)
            ->where('is_active', true)
            ->exists();

        if (!$valid) {
            return response()->json([
                'success' => false,
                'message' => 'API Key tidak valid.',
            ], 401);
        }

        return $next($request);
    }
}