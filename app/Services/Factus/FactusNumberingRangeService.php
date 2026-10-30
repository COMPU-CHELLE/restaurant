<?php

namespace App\Services\Factus;

use App\Models\Support\FactusService;
use Exception;

class FactusNumberingRangeService extends FactusHttpService
{
    protected FactusService $factus;

        public function __construct()
        {
            parent::__construct();
        }


    protected function client(FactusService $factus = null)
    {
        return parent::client($factus ?: $this->factus);
    }

    protected function handleResponse($response): array
    {
        if (! $response->successful()) {
            throw new Exception(json_encode([
                'message' => 'Factus API error',
                'status'  => $response->status(),
                'body'    => $response->json() ?? $response->body(),
            ]));
        }
        return $response->json();
    }

    // Listar rangos de numeración
    public function index(array $filters = []): array
    {
        $response = $this->client()->get($this->baseUrl() . '/numbering-ranges', [
            'filter' => $filters
        ]);
        return $this->handleResponse($response);
    }

    // Ver un rango
    public function show(int $numberingRangeId): array
    {
        $response = $this->client()->get($this->baseUrl() . '/numbering-ranges', [
            'filter' => ['id' => $numberingRangeId]
        ]);
        $result = $this->handleResponse($response);
        // Espera estructura: ['status'=>'OK', 'message'=>'...', 'data'=>['data'=>[...]]]
        if (
            is_array($result)
            && isset($result['data']['data'])
            && is_array($result['data']['data'])
            && isset($result['data']['data'][0])
        ) {
            return $result['data']['data'][0];
        }
        throw new \Exception('No se encontró el rango de numeración solicitado en Factus');
    }

    // Crear rango
    public function create(array $payload): array
    {
        $response = $this->client()->post($this->baseUrl() . '/numbering-ranges', $payload);
        return $this->handleResponse($response);
    }

    // Eliminar rango
    public function delete(int $numberingRangeId): array
    {
        $response = $this->client()->delete($this->baseUrl() . "/numbering-ranges/{$numberingRangeId}");
        return $this->handleResponse($response);
    }

    // Actualizar consecutivo
    public function updateCurrent(int $numberingRangeId, int $current): array
    {
        $response = $this->client()->put($this->baseUrl() . "/numbering-ranges/{$numberingRangeId}/current", [
            'current' => $current
        ]);
        return $this->handleResponse($response);
    }

    // Rangos asociados al software (DIAN)
    public function dianRanges(): array
    {
        $response = $this->client()->get($this->baseUrl() . '/numbering-ranges/dian');
        return $this->handleResponse($response);
    }
}
