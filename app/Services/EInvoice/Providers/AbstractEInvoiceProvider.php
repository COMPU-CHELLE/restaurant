<?php

namespace App\Services\EInvoice\Providers;

use App\Services\EInvoice\Contracts\EInvoiceProvider;
use App\Services\EInvoice\DTO\ElectronicDocument;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class AbstractEInvoiceProvider implements EInvoiceProvider
{
    /** Último payload construido; solo para dejarlo en el log si la emisión falla. */
    protected mixed $lastPayload = null;

    final public function issue(ElectronicDocument $document): array
    {
        $this->lastPayload = null;

        try {
            return $this->send($document);
        } catch (Throwable $e) {
            Log::error('Facturación electrónica error', [
                'country' => $this->country(),
                'provider' => $this->key(),
                'kind' => $document->kind->value,
                'document_id' => $document->documentId,
                'error' => $e->getMessage(),
                'payload' => $this->lastPayload,
            ]);

            throw $e;
        }
    }

    abstract protected function send(ElectronicDocument $document): array;
}
