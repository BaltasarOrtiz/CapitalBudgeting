<?php

namespace App\Services\IBM;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class IBMAuthService
{
    private string $tokenUrl;
    private string $grantType;

    public function __construct()
    {
        $this->tokenUrl = config('ibm.auth.token_url');
        $this->grantType = config('ibm.auth.grant_type');
    }

    /**
     * Obtener token de COS (con cache)
     */
    public function getCOSToken(): string
    {
        $cacheKey = config('ibm.cos.token_cache_key');
        $ttl = config('ibm.cos.token_ttl');

        return Cache::remember($cacheKey, $ttl, function () {
            return $this->requestToken(config('ibm.cos.api_key'));
        });
    }

    /**
     * Obtener token de Watson ML (con cache)
     */
    public function getWatsonToken(): string
    {
        $cacheKey = config('ibm.watson_ml.token_cache_key');
        $ttl = config('ibm.watson_ml.token_ttl');

        return Cache::remember($cacheKey, $ttl, function () {
            return $this->requestToken(config('ibm.watson_ml.api_key'));
        });
    }

    /**
     * Solicitar token a IBM IAM
     */
    private function requestToken(string $apiKey): string
    {
        try {
            $response = Http::asForm()->post($this->tokenUrl, [
                'grant_type' => $this->grantType,
                'apikey' => $apiKey,
            ]);

            if (!$response->successful()) {
                throw new Exception('Error obteniendo token IBM: ' . $response->body());
            }

            $data = $response->json();

            if (!isset($data['access_token'])) {
                throw new Exception('Token no encontrado en respuesta IBM');
            }

            return $data['access_token'];

        } catch (Exception $e) {
            Log::error('Error obteniendo token IBM', [
                'error' => $e->getMessage(),
                'api_key_prefix' => substr($apiKey, 0, 8) . '...'
            ]);
            throw $e;
        }
    }

    /**
     * Invalidar cache de tokens (útil para testing o errores)
     */
    public function clearTokenCache(): void
    {
        Cache::forget(config('ibm.cos.token_cache_key'));
        Cache::forget(config('ibm.watson_ml.token_cache_key'));
    }

    /**
     * Verificar si un token está en cache y es válido
     */
    public function hasValidToken(string $service): bool
    {
        $cacheKey = $service === 'cos'
            ? config('ibm.cos.token_cache_key')
            : config('ibm.watson_ml.token_cache_key');

        return Cache::has($cacheKey);
    }
}