<?php

namespace App\Services\Factus;

use App\Models\Tenant\FactusDocumentsRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

use App\Models\Support\FactusService;
use Exception;
use Illuminate\Support\Facades\DB;

class FactusDocumentService extends FactusHttpService
{
    protected FactusService $factus;

    public function __construct()
    {
        parent::__construct();
    }

    public function client(FactusService $factus = null)
    {
        return parent::client($factus ?: $this->factus);
    }

    public function create(array $payload, string $documentType, ?string $documentTable = null, ?int $documentId = null)
    {
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
            $publicUrl = data_get($data, 'data.support_document.public_url');

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
                'public_url'      => $publicUrl,
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

            // Enviar automáticamente la factura al email del cliente si está disponible
            $numeroFactura = data_get($data, 'data.support_document.number');
            $emailCliente = $payload['customer']['email'] ?? null;
            if ($numeroFactura && $emailCliente) {
                try {
                    $this->sendEmail($numeroFactura, $emailCliente);
                } catch (\Throwable $ex) {
                    // Loguear pero no interrumpir el flujo principal
                    Log::warning('No se pudo enviar la factura electrónica por email automáticamente', [
                        'numero' => $numeroFactura,
                        'email' => $emailCliente,
                        'error' => $ex->getMessage(),
                    ]);
                }
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


    public function emit(string $documentType, string $documentTable, int $documentId, array $payload = []): array
    {
        // Aquí deberías construir el payload y llamar a la API de Factus usando $this->factus
        // Ejemplo:
        // $response = $this->client($this->factus)->post($this->baseUrl($this->factus) . '/invoice', $payload);
        // Guardar en factus_documents_requests
        // ...
        return [
            'status' => 'success',
            'message' => 'Documento emitido',
            // 'cufe', 'pdf', 'xml', ...
        ];
    }

    /**
     * Reintenta la emisión de un documento.
     */
    public function retry(string $documentType, string $documentTable, int $documentId, array $payload = []): array
    {
        // Busca el último request y reintenta
        return $this->emit($documentType, $documentTable, $documentId, $payload);
    }

    /**
     * Consulta el estado actual en Factus/DIAN.
     */
    public function getStatus(string $documentType, string $documentTable, int $documentId): array
    {
        // Busca el último request y consulta la API de estado de Factus usando $this->factus
        // ...
        return [
            'status' => 'success',
            'message' => 'Estado consultado',
            // 'cufe', 'estado_dian', ...
        ];
    }

    /**
     * Retorna la URL pública de la DIAN para consultar el documento por CUFE.
     */
    public function getDianUrlByCufe(string $cufe): string
    {
        return 'https://catalogo-vpfe.dian.gov.co/document/searchqr?documentkey=' . $cufe;
    }

    /**
     * Retorna la URL de Factus para ver el documento (si aplica).
     */
    public function getFactusUrlByDocument(string $documentType, string $documentTable, int $documentId): ?string
    {
        $req = $this->getLastRequest($documentType, $documentTable, $documentId);
        if ($req && !empty($req->document_number)) {
            // Ejemplo: https://app.factus.com.co/documents/{document_number}
            return 'https://app.factus.com.co/documents/' . $req->document_number;
        }
        return null;
    }

    /**
     * Devuelve el último registro de factus_documents_requests para ese documento.
     */
    public function getLastRequest(string $documentType, string $documentTable, int $documentId): ?FactusDocumentsRequest
    {
        return FactusDocumentsRequest::where('document_type', $documentType)
            ->where('document_table', $documentTable)
            ->where('document_id', $documentId)
            ->latest('created_at')
            ->first();
    }

    /**
     * Reintenta todos los documentos con error/pending (para el scheduler).
     */
    public function bulkRetry(int $max = 20): int
    {
        $pendings = FactusDocumentsRequest::whereIn('status', ['error', 'pendiente'])
            ->orderBy('created_at')
            ->limit($max)
            ->get();
        $count = 0;
        foreach ($pendings as $req) {
            try {
                $this->retry($req->document_type, $req->document_table, $req->document_id);
                $count++;
            } catch (\Throwable $e) {
                Log::error('Factus bulkRetry error', ['id' => $req->codmov, 'error' => $e->getMessage()]);
            }
        }
        return $count;
    }

    public function sendEmail(string $number, string $email): array
    {
        $endpoint = "/support-documents/send-email/{$number}";
        $response = $this->client()->post($this->baseUrl() . $endpoint, ['email' => $email]);
        return $this->handleResponse($response);
    }
}
