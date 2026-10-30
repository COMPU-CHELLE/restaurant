<?php

namespace App\Services\Factus;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Support\FactusService;
use Exception;

class FactusCompanyService
{

    protected string $baseUrl;
    protected string $token;

    public function __construct(int $companyId, ?FactusAuthService $auth = null, ?string $environment = null)
    {
        $service = $this->resolveFactusService($companyId, $environment);
        $authService = $auth ?: app(FactusAuthService::class);

        $this->token = $authService->getToken($service);
        $this->baseUrl = $this->resolveBaseUrl($service);
    }

    protected function client()
    {
        return Http::withToken($this->token)
            ->acceptJson()
            ->contentType('application/json');
    }

    protected function handleResponse($response)
    {
        if ($response->failed()) {
            Log::error('Factus Company API error', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            throw new Exception(
                $response->json('message') ?? 'Error en comunicación con Factus'
            );
        }

        return $response->json('data', []);
    }

    /** ========================
     *  VER DATOS DE LA EMPRESA
     * ======================== */
    public function show(): array
    {
        $response = $this->client()->get("{$this->baseUrl}/company");
        return $this->handleResponse($response);
    }

    /** ========================
     *  ACTUALIZAR DATOS DE LA EMPRESA
     * ======================== */
    public function update(array $payload): array
    {
        $response = $this->client()->put("{$this->baseUrl}/company", $payload);
        return $this->handleResponse($response);
    }

    /** ========================
     *  ACTUALIZAR LOGO DE LA EMPRESA
     * ======================== */
    public function updateLogo(string $filePath): array
    {
        // Se asume que $filePath es path local del archivo
        $response = $this->client()->attach(
            'logo', fopen($filePath, 'r')
        )->post("{$this->baseUrl}/company/logo");

        return $this->handleResponse($response);
    }
}
