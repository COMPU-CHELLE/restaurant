<?php

namespace App\Services\Factus;

use App\Models\Tenant\FactusDocumentsRequest;
use Exception;
use Illuminate\Support\Facades\Log;

class FactusPayrollService extends FactusHttpService
{
    /**
     * Crea y valida una nómina electrónica ante Factus.
     * Referencia: https://developers.factus.com.co/nomina/crear-y-validar/
     */
    public function create(array $payload, ?string $documentTable = null, ?int $documentId = null)
    {
        $endpoint = '/payrolls';
        $response = null;

        try {
            $response = $this->client()->post($this->baseUrl() . $endpoint, $payload);
            $data = $this->handleResponse($response);

            $success = data_get($data, 'success', true);
            $payrollId = data_get($data, 'data.id') ?? data_get($data, 'data.payroll.id');
            $cune = data_get($data, 'data.cune') ?? data_get($data, 'data.payroll.cune');

            if (!$success || !$cune) {
                throw new Exception('Nómina electrónica rechazada por Factus');
            }

            $existing = $this->findExisting($documentTable, $documentId);

            $dataToSave = [
                'document_id_factus' => $payrollId,
                'reference_code' => $payload['reference_code'] ?? null,
                'document_number' => data_get($data, 'data.number'),
                'cufe' => $cune,
                'payload' => $payload,
                'response' => $data,
                'status' => 'OK',
                'error' => null,
                'environment' => $this->factus->environment,
                'endpoint' => $endpoint,
                'http_code' => $response->status(),
            ];

            $existing
                ? $existing->update($dataToSave)
                : FactusDocumentsRequest::create(array_merge([
                    'document_type' => 'Nómina electrónica',
                    'document_table' => $documentTable,
                    'document_id' => $documentId,
                ], $dataToSave));

            return $data;
        } catch (Exception $e) {
            $existing = $this->findExisting($documentTable, $documentId);

            $errorData = [
                'reference_code' => $payload['reference_code'] ?? null,
                'payload' => $payload,
                'status' => 'error',
                'error' => $e->getMessage(),
                'environment' => $this->factus->environment,
                'endpoint' => $endpoint,
                'http_code' => $response?->status(),
                'response' => $response ? ['body' => $response->body()] : null,
            ];

            $existing
                ? $existing->update($errorData)
                : FactusDocumentsRequest::create(array_merge([
                    'document_type' => 'Nómina electrónica',
                    'document_table' => $documentTable,
                    'document_id' => $documentId,
                ], $errorData));

            Log::error('Nómina electrónica error', [
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /** GET /v2/payrolls/reference/{reference_code} */
    public function show(string $referenceCode): array
    {
        $response = $this->client()->get($this->baseUrl() . "/payrolls/reference/{$referenceCode}");

        return $this->handleResponse($response);
    }

    /** GET /v2/payrolls con filtros (identification_number, number, names, page) */
    public function list(array $filters = []): array
    {
        $response = $this->client()->get($this->baseUrl() . '/payrolls', $filters);

        return $this->handleResponse($response);
    }

    /** DELETE /v2/payrolls/reference/{reference_code} — solo nóminas no validadas por la DIAN */
    public function delete(string $referenceCode): array
    {
        $response = $this->client()->delete($this->baseUrl() . "/payrolls/reference/{$referenceCode}");

        return $this->handleResponse($response);
    }

    /** GET /v2/payrolls/{number}/download-xml */
    public function downloadXml(string $number): array
    {
        $response = $this->client()->get($this->baseUrl() . "/payrolls/{$number}/download-xml");

        return $this->handleResponse($response);
    }

    private function findExisting(?string $documentTable, ?int $documentId): ?FactusDocumentsRequest
    {
        return FactusDocumentsRequest::where([
            'document_type' => 'Nómina electrónica',
            'document_table' => $documentTable,
            'document_id' => $documentId,
        ])->latest()->first();
    }
}
