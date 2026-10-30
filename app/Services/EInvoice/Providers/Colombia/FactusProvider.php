<?php

namespace App\Services\EInvoice\Providers\Colombia;

use App\Models\Support\FactusService;
use App\Services\EInvoice\DocumentKind;
use App\Services\EInvoice\DTO\ElectronicDocument;
use App\Services\EInvoice\Providers\AbstractEInvoiceProvider;
use App\Services\Factus\Builders\V1;
use App\Services\Factus\Builders\V2;
use App\Services\Factus\FactusAdjustmentNoteService;
use App\Services\Factus\FactusBillService;
use App\Services\Factus\FactusCreditNoteService;
use App\Services\Factus\FactusDocumentService;

/**
 * Colombia vía Factus. Contiene la lógica que antes estaba repetida en
 * SalesInvoiceService, SalesNoteService, PurchaseInvoiceService y
 * PurchaseNoteService: elegir builder (v1/v2) y servicio según el documento.
 */
class FactusProvider extends AbstractEInvoiceProvider
{
    public function key(): string
    {
        return 'factus';
    }

    public function country(): string
    {
        return 'CO';
    }

    public function label(): string
    {
        return 'Factus';
    }

    public function configRoute(): ?string
    {
        return 'soporte.factus.index';
    }

    public function configuredEnvironment(int $companyId): ?string
    {
        return FactusService::where('codemp', $companyId)->where('active', 1)->value('environment');
    }

    public function supports(DocumentKind $kind): bool
    {
        return array_key_exists($kind->value, $this->map());
    }

    protected function send(ElectronicDocument $document): array
    {
        [$service, $v1Builder, $v2Builder] = $this->map()[$document->kind->value];

        $builder = $this->apiVersion() === 'v2' ? $v2Builder : $v1Builder;

        $this->lastPayload = app($builder)->build(
            $document->document,
            $document->details,
            $document->prefix,
            $document->customer,
            $document->branch
        );

        $result = app($service)->create(
            payload: $this->lastPayload,
            documentType: $document->documentType(),
            documentTable: $document->documentTable(),
            documentId: $document->documentId
        );

        // Algunos servicios no declaran tipo de retorno
        return is_array($result) ? $result : (array) $result;
    }

    private function apiVersion(): string
    {
        $config = FactusService::where('codemp', session('company_id'))
            ->where('active', 1)
            ->first();

        return $config->api_version ?? 'v1';
    }

    /** DocumentKind => [servicio, builder v1, builder v2] */
    private function map(): array
    {
        return [
            DocumentKind::SalesInvoice->value => [
                FactusBillService::class,
                V1\SalesInvoiceFactusBuilder::class,
                V2\SalesInvoiceFactusBuilder::class,
            ],
            DocumentKind::SalesCreditNote->value => [
                FactusCreditNoteService::class,
                V1\FactusCreditNoteBuilder::class,
                V2\SalesNoteFactusBuilder::class,
            ],
            DocumentKind::PurchaseSupportDocument->value => [
                FactusDocumentService::class,
                V1\FactusPurchaseInvoiceBuilder::class,
                V2\PurchaseInvoiceFactusBuilder::class,
            ],
            DocumentKind::PurchaseAdjustmentNote->value => [
                FactusAdjustmentNoteService::class,
                V1\FactusPurchaseCreditNoteBuilder::class,
                V2\PurchaseNoteFactusBuilder::class,
            ],
        ];
    }
}
