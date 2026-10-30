<?php

namespace App\Services\Factus;

use App\Models\Support\FactusService;
use Illuminate\Support\Facades\Http;
use Exception;

class FactusReceptionService
{
    //use ResolvesFactusService;

    protected string $baseUrl;
    protected string $token;

    public function __construct(int|string $companyOrToken, bool $production = false, ?FactusAuthService $auth = null, ?string $environment = null)
    {
        if (is_int($companyOrToken)) {
            $service = $this->resolveFactusService($companyOrToken, $environment);
            $authService = $auth ?: app(FactusAuthService::class);

            $this->token = $authService->getToken($service);
            $this->baseUrl = $this->resolveBaseUrl($service);
            return;
        }

        $this->token = $companyOrToken;
        $this->baseUrl = $production
            ? 'https://api.factus.com.co/v1'
            : 'https://api-sandbox.factus.com.co/v1';
    }

    protected function client()
    {
        return Http::withToken($this->token)->acceptJson();
    }

    // Cargar factura
    public function uploadInvoice(array $payload): array
    {
        $response = $this->client()->post("{$this->baseUrl}/receptions/upload", $payload);

        if ($response->failed()) {
            throw new Exception($response->json('message') ?? 'Error al cargar factura');
        }

        return $response->json('data', []);
    }

    // Ver facturas
    public function bills(array $filters = []): array
    {
        $response = $this->client()->get("{$this->baseUrl}/receptions/bills", ['filter' => $filters]);

        if ($response->failed()) {
            throw new Exception($response->json('message') ?? 'Error al consultar facturas');
        }

        return $response->json('data', []);
    }

    // Emitir evento
    public function emitEvent(int $billId, string $eventType, array $payload = []): array
    {
        $response = $this->client()->post(
            "{$this->baseUrl}/receptions/bills/{$billId}/radian/events/{$eventType}",
            $payload
        );

        if ($response->failed()) {
            throw new Exception($response->json('message') ?? 'Error al emitir evento');
        }

        return $response->json('data', []);
    }
}
