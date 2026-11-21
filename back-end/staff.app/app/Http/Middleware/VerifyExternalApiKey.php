<?php

namespace App\Http\Middleware;

use App\Models\IntegrationApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyExternalApiKey
{
    /**
     * Handle an incoming external integration request for a given service key.
     */
    public function handle(Request $request, Closure $next, string $serviceKey = 'sk6_external'): Response
    {
        $providedKey = $request->header('X-API-Key') ?: $request->bearerToken();

        if (empty($providedKey)) {
            return response()->json([
                'status' => false,
                'message' => 'API Key diperlukan. Kirim melalui header X-API-Key atau Authorization: Bearer <API_KEY>.',
            ], 401);
        }

        $record = IntegrationApiKey::where('service_key', $serviceKey)
            ->where('is_active', true)
            ->first();

        if (!$record || !hash_equals($record->api_key, $providedKey)) {
            return response()->json([
                'status' => false,
                'message' => 'API Key tidak valid atau sudah dinonaktifkan.',
            ], 401);
        }

        $record->forceFill(['last_used_at' => now()])->saveQuietly();
        $request->attributes->set('integration_api_key', $record);

        return $next($request);
    }
}
