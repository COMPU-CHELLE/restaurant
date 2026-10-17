<?php

namespace App\Services\Factus;

use App\Models\Support\FactusService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use RuntimeException;

/**
 * Base de los servicios que llaman a la API de Factus (documentos, rangos, adquirentes…).
 * Diferencia con Chellnova: recibe la configuración de forma explícita en lugar de leer
 * company_id de la sesión, así también funciona en colas y comandos.
 */
abstract class FactusHttpService
{
    public function __construct(
        protected FactusService $factus,
        protected FactusAuthService $auth,
    ) {
    }

    protected function client(): PendingRequest
    {
        return \Illuminate\Support\Facades\Http::withToken($this->auth->getToken($this->factus))
            ->acceptJson()
            ->timeout(30);
    }

    protected function baseUrl(): string
    {
        return rtrim($this->factus->base_url, '/') . '/' . trim($this->factus->api_version, '/');
    }

    protected function handleResponse(Response $response): array
    {
        if (! $response->successful()) {
            throw new RuntimeException(json_encode([
                'message' => 'Factus API error',
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]));
        }

        return $response->json() ?? [];
    }
}
