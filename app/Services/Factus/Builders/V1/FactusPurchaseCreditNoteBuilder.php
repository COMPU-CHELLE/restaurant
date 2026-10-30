<?php

namespace App\Services\Factus\Builders\V1;

use App\Models\Tenant\ClientSupplier;
use App\Models\Tenant\PaymentForm;
use App\Models\Tenant\Prefix;
use App\Models\Tenant\PurchaseInvoice;
use Illuminate\Support\Facades\DB;

class FactusPurchaseCreditNoteBuilder
{
    /**
     * Genera el payload para Factus a partir de una nota crédito de compra
     * @param \Illuminate\Support\Collection|array $details
     * @param Prefix $prefix
     * @param mixed $branch
     * @return array
     */
    public function build(
        array $noteData,
        $details,
        Prefix $prefix,
        ClientSupplier $provider,
        $branch,
    ): array {
        $invoice = PurchaseInvoice::where('codmov', $noteData['codivo'])->first();
        if ($invoice && !empty($invoice->codmov)) {
            $factusData = DB::connection('tenant')
                ->table('factus_documents_requests')
                ->where('document_table', 'purchase_invoices')
                ->where('document_id', $invoice->codmov)
                ->orderByDesc('codmov')
                ->first();
            if ($factusData) {
                $documentSupport = $factusData->document_id_factus;
            }
        }
        $paymentMethod = PaymentForm::where('codmov', $noteData['forpag'])->first();
        $paymentForm = PaymentForm::where('codmov', $noteData['forpag'])->first();
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
                'price' => \App\Services\Factus\FactusItemMapper::priceWithTax($item),
                'tax_rate' => number_format($item['poriva'] ?? 0, 2, '.', ''),
                // Si undmed es un objeto, usar undmed->factus_id; si es array, undmed['factus_id']; si es numérico, usarlo directo; si no, 70
                'unit_measure_id'   => \App\Services\Factus\FactusItemMapper::unitMeasureId($item, $unitFactusIds),
                'standard_code_id'  => $item['standard_code_id'] ?? 1,
                'is_excluded'       => $isExcluded,
                'tribute_id'        => $item['tribute_id'] ?? 1,
                'withholding_taxes' => $item['withholding_taxes'] ?? [],
            ];
        })->values()->toArray();

        $allowanceCharges = $noteData['allowance_charges'] ?? [];

        $payload = [
            'reference_code' => trim(($noteData['prefij'] ?? '') . ($noteData['number'] ?? '')),
            'numbering_range_id' => $prefix->activeResolution->factus_id,
            'payment_method_code' => $paymentMethod->factus_id,
            'support_document_id' => $documentSupport,
            'correction_concept_code' => (string)($noteData['tipcop'] ?? ''),
            'observation' => $noteData['detail'] ?? '',
            'items' => $items,
        ];
        if (!empty($allowanceCharges)) {
            $payload['allowance_charges'] = $allowanceCharges;
        }
        return $payload;
    }
}
