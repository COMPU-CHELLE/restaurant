<?php

namespace App\Services\Factus;

use App\Models\Support\FactusService;
use Illuminate\Support\Facades\Http;
use Exception;

class FactusSubscriptionService
{
    use ResolvesFactusService;

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

    public function index(): array
    {
        $response = $this->client()->get("{$this->baseUrl}/subscriptions");

        if ($response->failed()) {
            throw new Exception($response->json('message') ?? 'Error al obtener suscripciones');
        }

        return $response->json('data', []);
    }
}
