<?php

namespace App\Services\IBM;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class IBMAuthService
{
    private string $tokenUrl;
    private string $grantType;
    private  $authToken;

    public function __construct()
    {
        $this->tokenUrl = config('ibm.auth.token_url');
        $this->grantType = config('ibm.auth.grant_type');
        $this->authToken = Auth::user()->ibm_token;
    }

    /**
     * Solicitar token a IBM IAM
     */
    public function requestToken(): string
    {
        try {
            $response = Http::asForm()->post($this->tokenUrl, [
                'grant_type' => $this->grantType,
                'apikey' => config('ibm.cos.api_key'),
            ]);

            $data = $response->json();

            $this->authToken = $data['access_token'];
            return $data['access_token'];

        } catch (Exception $e) {
            Log::error('Error obteniendo token IBM', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getToken(): string
    {
        if (!$this->authToken) {
            $this->authToken = $this->requestToken();
            User::where('id', Auth::id())->update(['ibm_token' => $this->authToken]);
        }
        return $this->authToken;
    }
}
