<?php

namespace App\Services\Factus;

use App\Models\Support\FactusService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class FactusAuthService
{
    public function getToken(FactusService $factus): string
    {
        // margen de 60 segundos
        if ($factus->token && $factus->token_expires_at?->gt(now()->addSeconds(60))) {
            return $factus->token;
        }

        if ($factus->hasRefreshToken()) {
            try {
                return $this->refreshToken($factus);
            } catch (\Exception $e) {
                Log::warning('[FactusAuthService] Refresh falló, usando login', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $this->passwordLogin($factus);
    }

    /* =========================
     |  PASSWORD LOGIN
     ========================= */

    protected function passwordLogin(FactusService $factus): string
    {
        Log::debug('Factus login payload', [
            'client_id' => $factus->client_id,
            'client_secret' => $factus->client_secret,
            'username' => $factus->username,
            'password' => $factus->password,
        ]);

        $response = Http::asForm()->timeout(20)->post(
            $this->tokenUrl($factus),
            [
                'grant_type'     => 'password',
                'client_id'      => $factus->client_id,
                'client_secret'  => $factus->client_secret,
                'username'       => $factus->username,
                'password'       => $factus->password,
            ]
        );

        if (! $response->successful()) {
            throw new Exception(
                'Factus auth error: ' . ($response->json('error_description') ?? $response->body())
            );
        }

        return $this->storeTokenData($factus, $response->json());
    }

    protected function refreshToken(FactusService $factus): string
    {
        $response = Http::asForm()
            ->withHeaders(['Accept' => 'application/json'])
            ->post(
                $this->tokenUrl($factus),
                [
                    'grant_type'     => 'refresh_token',
                    'client_id'      => $factus->client_id,
                    'client_secret' => $factus->client_secret,
                    'refresh_token' => $factus->refresh_token,
                ]
            );

        if (! $response->successful()) {
            throw new Exception('Factus refresh error: ' . $response->body());
        }

        return $this->storeTokenData($factus, $response->json());
    }

    protected function storeTokenData(FactusService $factus, array $data): string
    {
        $factus->update([
            'token'            => $data['access_token'],
            'refresh_token'    => $data['refresh_token'] ?? $factus->refresh_token,
            'token_expires_at' => Carbon::now()->addSeconds($data['expires_in']),
        ]);

        return $data['access_token'];
    }

    public function testLogin(array $data): array
    {

        // Crear instancia temporal del modelo con los datos recibidos
        $factus = new FactusService($data);
        try {
            $response = Http::asForm()->post(
                $this->tokenUrl($factus),
                [
                    'grant_type'     => 'password',
                    'client_id'      => $factus->client_id,
                    'client_secret'  => $factus->client_secret,
                    'username'       => $factus->username,
                    'password'       => $factus->password,
                ]
            );
            if (! $response->successful()) {
                return [
                    'success' => false,
                    'error' => $response->json('error_description') ?? $response->body(),
                ];
            }

            // Si existe un registro real, guárdalo solo si el environment y company_id coinciden
            if (!empty($data['company_id']) && !empty($data['environment'])) {
                $real = FactusService::where('codemp', $data['company_id'])
                    ->where('environment', $data['environment'])
                    ->first();
                if ($real) {
                    $real->update([
                        'token'            => $response->json('access_token'),
                        'refresh_token'    => $response->json('refresh_token') ?? $real->refresh_token,
                        'token_expires_at' => Carbon::now()->addSeconds($response->json('expires_in')),
                    ]);
                }
            }

            return [
                'success' => true,
                'token' => $response->json('access_token'),
                'refresh_token' => $response->json('refresh_token'),
                'expires_in' => $response->json('expires_in'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function tokenUrl(FactusService $factus): string
    {
        return match ($factus->environment) {
            'sandbox' => 'https://api-sandbox.factus.com.co/oauth/token',
            default   => 'https://api.factus.com.co/oauth/token',
        };
    }
}
