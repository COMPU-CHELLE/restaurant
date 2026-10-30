<?php

namespace App\Services\Factus\Builders\V1;

use App\Models\Tenant\ClientSupplier;
use App\Models\Tenant\PaymentForm;
use App\Models\Tenant\Prefix;
use App\Models\Tenant\SalesInvoice;
use App\Models\Tenant\SalesNote;
use Illuminate\Support\Facades\DB;

class FactusCreditNoteBuilder
{
    /**
     * Construye el payload para Factus a partir de los datos de la factura.
     *
     * @param array $noteData
     * @param \Illuminate\Support\Collection|array $details
     * @param Prefix $prefix
     * @param ClientSupplier $customer
     * @param mixed $branch
     * @return array
     */
    public function build(
        array $noteData,
        $details,
        Prefix $prefix,
        ClientSupplier $customer,
        $branch,
    ): array {
        $customizationId = 20; // Por defecto para nota crédito
        $invoice = SalesInvoice::where('codmov', $noteData['codivo'])->first();
        if ($invoice && !empty($invoice->codmov)) {
            $factusData = DB::connection('tenant')
                ->table('factus_documents_requests')
                ->where('document_table', 'sales_invoices')
                ->where('document_id', $invoice->codmov)
                ->orderByDesc('codmov')
                ->first();
            if ($factusData) {
                $billId = $factusData->document_id_factus;
            }
        }

        $paymentMethod = PaymentForm::where('codmov', $noteData['forpag'])->first();
        $establishment = [
            'name' => $branch->name ?? '',
            'address' => $branch->address ?? '',
            'phone_number' => $branch->phone ?? '',
            'email' => $branch->email ?? $noteData['company_email'] ?? '',
            'municipality_id' => $branch->subzone->factus_id ?? null,
        ];
        $customerBlock = [
            'identification' => $customer->nitnum,
            'dv' => $customer->nitdig,
            'company' => $customer->razsoc ?? '',
            'trade_name' => $customer->razsoc ?? '',
            'names' => trim(($customer->nomuno ?? '') . ' ' . ($customer->nomdos ?? '') . ' ' . ($customer->apeuno ?? '') . ' ' . ($customer->apedos ?? '')),
            'address' => $customer->address ?? '',
            'email' => $customer->email ?? '',
            'phone' => $customer->phone ?? '',
            'legal_organization_id' => $customer->legal_organization_id ?? 2,
            'tribute_id' => $customer->tribute_id ?? 21,
            'identification_document_id' => $customer->tipdoc->factus_id ?? '', // <-- Aquí llega null según el usuario
            'municipality_id' => $customer->subzone->factus_id ?? null,
        ];
        $unitFactusIds = \App\Services\Factus\FactusItemMapper::unitFactusIds($details);

        $items = collect($details)->map(function ($item) {
            // Si es objeto, lo convertimos a array
            if (is_object($item)) {
                $item = (array) $item;
            }
            $isExcluded = 0;
            if ((float)($item['poriva'] ?? 0) === 0.0) {
                $isExcluded = 1;
            } elseif (isset($item['is_excluded'])) {
                $isExcluded = $item['is_excluded'] ? 1 : 0;
            }
            return [
                'code_reference' => $item['refere'] ?? '',
                'name' => $item['name'] ?? '',
                'quantity' => (float) ($item['cantid'] ?? 0),
                'discount_rate' => \App\Services\Factus\FactusItemMapper::discountRate($item),
                'price' => (float) ($item['valund'] ?? 0),
                'tax_rate' => number_format($item['poriva'] ?? 0, 2, '.', ''),
                // Si undmed es un objeto, usar undmed->factus_id; si es array, undmed['factus_id']; si es numérico, usarlo directo; si no, 70
                'unit_measure_id'   => \App\Services\Factus\FactusItemMapper::unitMeasureId($item, $unitFactusIds),
                'standard_code_id'  => $item['standard_code_id'] ?? 1,
                'is_excluded'       => $isExcluded,
                'tribute_id'        => $item['tribute_id'] ?? 1,
                'withholding_taxes' => $item['withholding_taxes'] ?? [],
            ];
        })->values()->toArray();

        $payload = [
            'numbering_range_id' => $prefix->activeResolution->factus_id,
            'correction_concept_code' => $noteData['tipcop'] ?? null,
            'customization_id' => $customizationId,
            'bill_id' => $billId,
            'reference_code' => trim(($noteData['prefij'] ?? '') . ($noteData['number'] ?? '')),
            'observation' => $noteData['detail'] ?? '',
            'payment_method_code' => $paymentMethod->factus_id,
            'establishment' => $establishment,
            'customer' => $customerBlock,
            'items' => $items,
        ];
        return $payload;
    }
}
