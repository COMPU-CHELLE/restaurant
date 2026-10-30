<?php

namespace App\Services\Factus;

use App\Services\Factus\FactusHttpService;
use App\Services\Factus\FactusAuthService;
use App\Models\Support\FactusService;
use App\Models\Tenant\FactusDocumentsRequest;
use Exception;
use Illuminate\Support\Facades\DB;

class FactusCreditNoteService extends FactusHttpService
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

    public function create(array $payload, string $documentType, ?string $documentTable = null, ?int $documentId = null)
    {
        $endpoint = '/credit-notes/validate';
        $response = null;
        $companyId = session('company_id');

        try {
            $response = $this->client()->post($this->baseUrl() . $endpoint, $payload);
            $data = $this->handleResponse($response);
            // 🔥 VALIDACIÓN REAL
            $success = data_get($data, 'success', true);
            $cude =    data_get($data, 'data.credit_note.cude');
            $qr = data_get($data, 'data.credit_note.qr');
            $qrImage = data_get($data, 'data.credit_note.qr_image');
            $publicUrl = data_get($data, 'data.credit_note.public_url');

            if (! $success || ! $cude) {
                throw new Exception('Nota rechazada por Factus');
            }

            $existing = FactusDocumentsRequest::where([
                'document_type'  => $documentType,
                'document_table' => $documentTable,
                'document_id'    => $documentId,
            ])->latest()->first();

            $dataToSave = [
                'document_id_factus' => data_get($data, 'data.credit_note.id'),
                'reference_code'  => $payload['reference_code'] ?? null,
                'document_number' => data_get($data, 'data.credit_note.number'),
                'qr'              => $qr,
                'qr_image'        => $qrImage,
                'public_url'      => $publicUrl,
                'cufe'            => $cude,
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

                DB::connection('mysql')->table('plan_quote_consumed')->where('codemp', $companyId)->increment('doccon');
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

    public function getByNumber($number)
    {
        $endpoint = "/credit-notes/{$number}";
        $response = $this->client($this->factus)->get($this->baseUrl($this->factus) . $endpoint);
        return $this->handleResponse($response);
    }

    public function list(array $filters = [])
    {
        $endpoint = '/credit-notes';
        $url = $this->baseUrl($this->factus) . $endpoint;
        if (!empty($filters)) {
            $url .= '?' . http_build_query($filters);
        }
        $response = $this->client($this->factus)->get($url);
        return $this->handleResponse($response);
    }

    public function downloadPdf($number)
    {
        $endpoint = "/credit-notes/download-pdf/{$number}";
        $response = $this->client($this->factus)->get($this->baseUrl($this->factus) . $endpoint);
        return $this->handleResponse($response);
    }

    /**
     * Descargar XML de la nota crédito
     * GET /v1/credit-notes/download-xml/{number}
     */
    public function downloadXml($number)
    {
        $endpoint = "/credit-notes/download-xml/{$number}";
        $response = $this->client($this->factus)->get($this->baseUrl($this->factus) . $endpoint);
        return $this->handleResponse($response);
    }

    /**
     * Obtener contenido de correo
     * GET /v1/credit-notes/{number}/email-content
     */
    public function getEmailContent($number)
    {
        $endpoint = "/credit-notes/{$number}/email-content";
        $response = $this->client($this->factus)->get($this->baseUrl($this->factus) . $endpoint);
        return $this->handleResponse($response);
    }

    /**
     * Eliminar nota crédito por código de referencia
     * DELETE /v1/credit-notes/reference/{reference_code}
     */
    public function deleteByReference($referenceCode)
    {
        $endpoint = "/credit-notes/reference/{$referenceCode}";
        $response = $this->client($this->factus)->delete($this->baseUrl($this->factus) . $endpoint);
        return $this->handleResponse($response);
    }

    /**
     * Enviar correo de nota crédito
     * POST /v1/credit-notes/send-email/{number}
     */
    public function sendEmail($number, array $payload)
    {
        $endpoint = "/credit-notes/send-email/{$number}";
        $response = $this->client($this->factus)->post($this->baseUrl($this->factus) . $endpoint, $payload);
        return $this->handleResponse($response);
    }
}
