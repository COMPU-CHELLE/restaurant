<?php

namespace App\Services\Factus;

use App\Models\Support\FactusService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Autenticación OAuth2 con Factus (password grant + refresh token), portada de Chellnova.
 * Diferencias: nunca escribe credenciales en el log y la URL del token sale de la URL base
 * de la configuración (que debe ser un host oficial, ver config/factus.php).
 */
class FactusAuthService
{
    public function getToken(FactusService $factus): string
    {
        // Margen de 60 segundos para no usar un token a punto de vencer.
        if ($factus->token && $factus->token_expires_at?->gt(now()->addSeconds(60))) {
            return $factus->token;
        }

        if ($factus->hasRefreshToken()) {
            try {
                return $this->refreshToken($factus);
            } catch (Throwable) {
                Log::warning('[Factus] El refresh token falló; se usa login con usuario y contraseña.', [
                    'codemp' => $factus->codemp,
                ]);
            }
        }

        return $this->passwordLogin($factus);
    }

    protected function passwordLogin(FactusService $factus): string
    {
        $response = Http::asForm()->acceptJson()->timeout(20)->post($this->tokenUrl($factus), [
            'grant_type' => 'password',
            'client_id' => $factus->client_id,
            'client_secret' => $factus->client_secret,
            'username' => $factus->username,
            'password' => $factus->password,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->errorMessage($response));
        }

        return $this->storeTokenData($factus, $response->json());
    }

    protected function refreshToken(FactusService $factus): string
    {
        $response = Http::asForm()->acceptJson()->timeout(20)->post($this->tokenUrl($factus), [
            'grant_type' => 'refresh_token',
            'client_id' => $factus->client_id,
            'client_secret' => $factus->client_secret,
            'refresh_token' => $factus->refresh_token,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->errorMessage($response));
        }

        return $this->storeTokenData($factus, $response->json());
    }

    protected function storeTokenData(FactusService $factus, array $data): string
    {
        $factus->forceFill([
            'token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $factus->refresh_token,
            'token_expires_at' => Carbon::now()->addSeconds((int) ($data['expires_in'] ?? 3600)),
        ])->save();

        return $data['access_token'];
    }

    /**
     * Prueba las credenciales sin tocar los datos guardados, salvo el token si ya existe la configuración.
     *
     * @param  array  $credentials  base_url, client_id, client_secret, username, password
     * @return array{success:bool, error?:string, expires_in?:int}
     */
    public function testLogin(array $credentials, ?FactusService $stored = null): array
    {
        $probe = (new FactusService())->forceFill($credentials);

        try {
            $response = Http::asForm()->acceptJson()->timeout(20)->post($this->tokenUrl($probe), [
                'grant_type' => 'password',
                'client_id' => $probe->client_id,
                'client_secret' => $probe->client_secret,
                'username' => $probe->username,
                'password' => $probe->password,
            ]);

            if (! $response->successful()) {
                return ['success' => false, 'error' => $this->errorMessage($response)];
            }

            if ($stored) {
                $this->storeTokenData($stored, $response->json());
            }

            return ['success' => true, 'expires_in' => (int) $response->json('expires_in', 0)];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => 'No se pudo contactar a Factus: ' . $e->getMessage()];
        }
    }

    protected function tokenUrl(FactusService $factus): string
    {
        return rtrim((string) $factus->base_url, '/') . '/oauth/token';
    }

    protected function errorMessage($response): string
    {
        $detail = $response->json('error_description') ?? $response->json('message') ?? 'respuesta ' . $response->status();

        return 'Factus rechazó la autenticación: ' . $detail;
    }
}
