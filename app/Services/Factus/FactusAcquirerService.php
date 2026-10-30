<?php

namespace App\Services\Factus;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Support\FactusService;
use Exception;

class FactusAcquirerService
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
            Log::error('Factus Acquirer API error', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            throw new Exception(
                $response->json('message') ?? 'Error en comunicación con Factus'
            );
        }

        return $response->json('data', []);
    }

    /**
     * Obtener datos de adquiriente por tipo de documento y número
     */
    public function get(string $identificationDocumentId, string $identificationNumber): array
    {
        $response = $this->client()
            ->get("{$this->baseUrl}/dian/acquirer", [
                'identification_document_id' => $identificationDocumentId,
                'identification_number' => $identificationNumber
            ]);

        return $this->handleResponse($response);
    }
}
