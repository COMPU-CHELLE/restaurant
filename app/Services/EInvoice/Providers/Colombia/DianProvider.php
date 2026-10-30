<?php

namespace App\Services\EInvoice\Providers\Colombia;

use App\Models\Support\DianService as DianConfig;
use App\Services\Dian\Builders\DianAdjustmentNoteBuilder;
use App\Services\Dian\Builders\DianCreditNoteBuilder;
use App\Services\Dian\Builders\DianInvoiceBuilder;
use App\Services\Dian\Builders\DianSupportDocumentBuilder;
use App\Services\Dian\DianDocumentService;
use App\Services\EInvoice\DocumentKind;
use App\Services\EInvoice\DTO\ElectronicDocument;
use App\Services\EInvoice\Providers\AbstractEInvoiceProvider;

/**
 * Colombia con conexión directa a la DIAN (XML UBL + firma propia).
 */
class DianProvider extends AbstractEInvoiceProvider
{
    public function key(): string
    {
        return 'dian';
    }

    public function country(): string
    {
        return 'CO';
    }

    public function label(): string
    {
        return 'DIAN (directo)';
    }

    public function configRoute(): ?string
    {
        return 'soporte.dian.index';
    }

    public function configuredEnvironment(int $companyId): ?string
    {
        return DianConfig::where('codemp', $companyId)->where('active', 1)->value('environment');
    }

    public function supports(DocumentKind $kind): bool
    {
        return array_key_exists($kind->value, $this->builders());
    }

    protected function send(ElectronicDocument $document): array
    {
        $provider = app(DianDocumentService::class, [
            'builderClass' => $this->builders()[$document->kind->value],
        ]);

        // Mismo orden que espera DianDocumentService::create()
        $this->lastPayload = [
            $document->document,
            $document->details,
            $document->prefix,
            $document->customer,
            $document->branch,
        ];

        $result = $provider->create(
            payload: $this->lastPayload,
            documentType: $document->documentType(),
            documentTable: $document->documentTable(),
            documentId: $document->documentId
        );

        // Algunos servicios no declaran tipo de retorno
        return is_array($result) ? $result : (array) $result;
    }

    /** DocumentKind => clase builder */
    private function builders(): array
    {
        return [
            DocumentKind::SalesInvoice->value => DianInvoiceBuilder::class,
            DocumentKind::SalesCreditNote->value => DianCreditNoteBuilder::class,
            DocumentKind::PurchaseSupportDocument->value => DianSupportDocumentBuilder::class,
            DocumentKind::PurchaseAdjustmentNote->value => DianAdjustmentNoteBuilder::class,
        ];
    }
}
