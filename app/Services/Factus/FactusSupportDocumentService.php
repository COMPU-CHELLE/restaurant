<?php

namespace App\Services\Factus;

use App\Models\Support\FactusService;
use App\Models\Tenant\FactusDocumentsRequest;
use Exception;
use Illuminate\Support\Facades\DB;

class FactusSupportDocumentService extends FactusHttpService
{
    protected FactusService $factus;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Crear / validar documento soporte
     */
    public function create(
        array $payload,
        string $documentType,
        ?string $documentTable = null,
        ?int $documentId = null
    ) {
        $endpoint = '/support-documents/validate';
        $response = null;
        $companyId = session('company_id');

        try {
            $response = $this->client()->post($this->baseUrl() . $endpoint, $payload);
            $data = $this->handleResponse($response);

            // 🔥 VALIDACIÓN REAL
            $success = data_get($data, 'success', true);
            $cufe = data_get($data, 'data.support_document.cuds');
            $qr = data_get($data, 'data.support_document.qr');
            $qrImage = data_get($data, 'data.support_document.qr_image');

            if (! $success || ! $cufe) {
                throw new Exception('Factura rechazada por Factus');
            }

            $existing = FactusDocumentsRequest::where([
                'document_type'  => $documentType,
                'document_table' => $documentTable,
                'document_id'    => $documentId,
            ])->latest()->first();

            $dataToSave = [
                'document_id_factus' => data_get($data, 'data.support_document.id'),
                'reference_code'  => $payload['reference_code'] ?? null,
                'document_number' => data_get($data, 'data.support_document.number'),
                'qr'              => $qr,
                'qr_image'        => $qrImage,
                'public_url'      => null,
                'cufe'            => $cufe,
                'payload'         => $payload,
                'response'        => $data,
                'status'          => 'OK',
                'environment'     => $this->factus->environment,
                'endpoint'        => $endpoint,
                'http_code'       => $response->status(),
            ];

            if ($existing) {
                $existing->update($dataToSave);
            } else {
                FactusDocumentsRequest::create(array_merge([
                    'document_type'  => $documentType,
                    'document_table' => $documentTable,
                    'document_id'    => $documentId,
                ], $dataToSave));

                DB::connection('mysql')->table('plan_quote_consumed')
                    ->where('codemp', $companyId)
                    ->increment('doccon');
            }

            return $data;

        } catch (Exception $e) {

            $existing = FactusDocumentsRequest::where([
                'document_type'  => $documentType,
                'document_table' => $documentTable,
                'document_id'    => $documentId,
            ])->latest()->first();

            $errorData = [
                'reference_code' => $payload['reference_code'] ?? null,
                'payload'        => $payload,
                'status'         => 'error',
                'error'          => $e->getMessage(),
                'environment'    => $this->factus->environment,
                'endpoint'       => $endpoint,
                'http_code'      => $response?->status(),
                'response'       => $response?->body(),
            ];

            if ($existing) {
                $existing->update($errorData);
            } else {
                FactusDocumentsRequest::create(array_merge([
                    'document_type'  => $documentType,
                    'document_table' => $documentTable,
                    'document_id'    => $documentId,
                ], $errorData));
            }

            throw $e;
        }
    }

    /**
     * Obtener documento soporte por número
     */
    public function getByNumber(string $number): array
    {
        $endpoint = "/support-documents/show/{$number}";
        $response = $this->client()->get($this->baseUrl() . $endpoint);
        return $this->handleResponse($response);
    }

    /**
     * Listar documentos soporte
     */
    public function list(array $filters = []): array
    {
        $endpoint = '/support-documents';
        $response = $this->client()->get($this->baseUrl() . $endpoint, $filters);
        return $this->handleResponse($response);
    }


    /**
     * Descargar PDF del documento soporte
     */
    public function downloadPdf(string $number): array
    {
        $endpoint = "/support-documents/download-pdf/{$number}";
        $response = $this->client()->get($this->baseUrl() . $endpoint);
        return $this->handleResponse($response);
    }

    /**
     * Descargar XML del documento soporte
     */
    public function downloadXml(string $number): array
    {
        $endpoint = "/support-documents/download-xml/{$number}";
        $response = $this->client()->get($this->baseUrl() . $endpoint);
        return $this->handleResponse($response);
    }

    /**
     * Eliminar documento soporte no validado
     * por código de referencia
     */
    public function deleteByReference(string $referenceCode): array
    {
        $endpoint = "/support-documents/reference/{$referenceCode}";
        $response = $this->client()->delete($this->baseUrl() . $endpoint);
        return $this->handleResponse($response);
    }

    /**
     * Emitir evento RADIAN sobre documento soporte
     */
    public function emitirEventoRadian(
        $billId,
        string $eventType,
        array $payload,
        ?string $documentTable = null,
        ?int $documentId = null
    ): array {
        $endpoint = "/receptions/bills/{$billId}/radian/events/{$eventType}";

        try {

            $response = $this->client()->post(
                $this->baseUrl() . $endpoint,
                $payload
            );

            $data = $this->handleResponse($response);

            FactusDocumentsRequest::create([
                'document_type'   => 'SupportDocumentEvent',
                'document_table'  => $documentTable,
                'document_id'     => $documentId,
                'reference_code'  => $payload['reference_code'] ?? null,
                'document_number' => $billId,
                'payload'         => $payload,
                'response'        => $data,
                'status'          => 'success',
                'environment'     => $this->factus->environment,
                'endpoint'        => $endpoint,
                'http_code'       => $response->status(),
            ]);

            return $data;

        } catch (Exception $e) {

            FactusDocumentsRequest::create([
                'document_type'   => 'SupportDocumentEvent',
                'document_table'  => $documentTable,
                'document_id'     => $documentId,
                'reference_code'  => $payload['reference_code'] ?? null,
                'document_number' => $billId,
                'payload'         => $payload,
                'status'          => 'error',
                'error'           => $e->getMessage(),
                'environment'     => $this->factus->environment,
                'endpoint'        => $endpoint,
            ]);

            throw $e;
        }
    }
}
