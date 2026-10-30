<?php

namespace App\Services\Factus;

use App\Services\Factus\FactusHttpService;
use App\Services\Factus\FactusAuthService;
use App\Models\Support\FactusService;
use App\Models\Tenant\FactusDocumentsRequest;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FactusBillService extends FactusHttpService
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
        $endpoint = '/bills/validate';
        $response = null;
        $companyId = session('company_id');


        try {
            $response = $this->client()->post($this->baseUrl() . $endpoint, $payload);
            $data = $this->handleResponse($response);
            // 🔥 VALIDACIÓN REAL
            $success = data_get($data, 'success', true);
            $cufe = data_get($data, 'data.bill.cufe');
            $qr = data_get($data, 'data.bill.qr');
            $qrImage = data_get($data, 'data.bill.qr_image');
            $publicUrl = data_get($data, 'data.bill.public_url');

            if (! $success || ! $cufe) {
                throw new Exception('Factura rechazada por Factus');
            }

            $existing = FactusDocumentsRequest::where([
                'document_type'  => $documentType,
                'document_table' => $documentTable,
                'document_id'    => $documentId,
            ])->latest()->first();

            $dataToSave = [
                'document_id_factus' => data_get($data, 'data.bill.id'),
                'reference_code'  => $payload['reference_code'] ?? null,
                'document_number' => data_get($data, 'data.bill.number'),
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
            $numeroFactura = data_get($data, 'data.bill.number');
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

    /**
     * Obtener detalle de factura por número
     */
    public function getBill(string $number): array
    {
        $endpoint = "/bills/show/{$number}";
        $response = $this->client()->get($this->baseUrl() . $endpoint);
        return $this->handleResponse($response);
    }

    /**
     * Listar facturas con filtros
     */
    public function listBills(array $filters = []): array
    {
        $endpoint = "/bills";
        $response = $this->client()->get($this->baseUrl() . $endpoint, $filters);
        return $this->handleResponse($response);
    }

    /**
     * Descargar PDF de la factura
     */
    public function downloadPdf(string $number): array
    {
        $endpoint = "/bills/download-pdf/{$number}";
        $response = $this->client()->get($this->baseUrl() . $endpoint);
        return $this->handleResponse($response);
    }

    /**
     * Descargar XML de la factura
     */
    public function downloadXml(string $number): array
    {
        $endpoint = "/bills/download-xml/{$number}";
        $response = $this->client()->get($this->baseUrl() . $endpoint);
        return $this->handleResponse($response);
    }

    /**
     * Eliminar factura no validada por código de referencia
     */
    public function destroyByReference(string $referenceCode): array
    {
        $endpoint = "/bills/destroy/reference/{$referenceCode}";
        $response = $this->client()->delete($this->baseUrl() . $endpoint);
        return $this->handleResponse($response);
    }

    /**
     * Obtener eventos RADIAN de la factura
     */
    public function getRadianEvents(string $number): array
    {
        $endpoint = "/bills/{$number}/radian/events";
        $response = $this->client()->get($this->baseUrl() . $endpoint);
        return $this->handleResponse($response);
    }

    /**
     * Actualizar evento RADIAN (aceptación, etc.)
     */
    public function updateRadianEvent(string $number, string $eventType, array $payload): array
    {
        $endpoint = "/bills/radian/events/update/{$number}/{$eventType}";
        $response = $this->client()->post($this->baseUrl() . $endpoint, $payload);
        return $this->handleResponse($response);
    }

    /**
     * Reenviar factura por correo
     */
    public function sendEmail(string $number, string $email): array
    {
        $endpoint = "/bills/send-email/{$number}";
        $response = $this->client()->post($this->baseUrl() . $endpoint, ['email' => $email]);
        return $this->handleResponse($response);
    }

    /**
     * Obtener contenido de correo enviado
     */
    public function getEmailContent(string $number): array
    {
        $endpoint = "/bills/{$number}/email-content";
        $response = $this->client()->get($this->baseUrl() . $endpoint);
        return $this->handleResponse($response);
    }
}
