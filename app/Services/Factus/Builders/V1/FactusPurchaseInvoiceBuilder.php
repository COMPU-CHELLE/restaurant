<?php

namespace App\Services\Factus\Builders\V1;

use App\Models\Tenant\PurchaseInvoice;
use App\Models\Tenant\PurchaseInvoiceDetail;
use App\Models\Tenant\ClientSupplier;
use App\Models\Tenant\PaymentForm;
use App\Models\Tenant\Prefix;
use Carbon\Carbon;

class FactusPurchaseInvoiceBuilder
{
    /**
     * Genera el payload para Factus a partir de una factura de compra
     * @param PurchaseInvoice $invoice
     * @param \Illuminate\Support\Collection|array $details
     * @param Prefix $prefix
     * @param ClientSupplier $provider
     * @return array
     */
    public function build(array $invoiceData, $details, Prefix $prefix, ClientSupplier $provider, $branch): array
    {
        $paymentForm = PaymentForm::where('codmov', $invoiceData['forpag'])->first();
        $paymentMethod = PaymentForm::where('codmov', $invoiceData['tippag'])->first();
        $establishment = [
            'name' => $branch->name ?? '',
            'address' => $branch->address ?? '',
            'phone_number' => $branch->phone ?? '',
            // Si la sede no tiene email, usar el global de la empresa si está en $invoiceData['company_email']
            'email' => $branch->email ?? $invoiceData['company_email'] ?? '',
            'municipality_id' => $branch->subzone->factus_id ?? null,
        ];
        $customerBlock = [
            'identification' => $provider->nitnum,
            'dv' => $provider->nitdig,
            'company' => $provider->razsoc ?? '',
            'trade_name' => $provider->razsoc ?? '',
            'names' => trim(($provider->nomuno ?? '') . ' ' . ($provider->nomdos ?? '') . ' ' . ($provider->apeuno ?? '') . ' ' . ($provider->apedos ?? '')),
            'address' => $provider->address ?? '',
            'email' => $provider->email ?? '',
            'phone' => $provider->phone ?? '',
            'legal_organization_id' => $provider->legal_organization_id ?? 2,
            'tribute_id' => $provider->tribute_id ?? 21,
            'identification_document_id' => $provider->tipdoc->factus_id ?? '', // <-- Aquí llega null según el usuario
            'country_code' => $provider->codpai ?? 'CO',
            'municipality_id' => $provider->subzone->factus_id ?? null,
        ];

        $unitFactusIds = \App\Services\Factus\FactusItemMapper::unitFactusIds($details);

        $items = collect($details)->map(function ($item) use ($unitFactusIds) {
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
                'unit_measure_id'   => \App\Services\Factus\FactusItemMapper::unitMeasureId($item, $unitFactusIds),
                'standard_code_id'  => $item['standard_code_id'] ?? 1,
                'is_excluded'       => $isExcluded,
                'tribute_id'        => $item['tribute_id'] ?? 1,
                'withholding_taxes' => $item['withholding_taxes'] ?? [],
            ];
        })->values()->toArray();

        $allowanceCharges = $invoiceData['allowance_charges'] ?? [];

            $payload = [
                'reference_code' => trim(($invoiceData['prefij'] ?? '') . ($invoiceData['number'] ?? '')),
                'numbering_range_id' => $prefix->activeResolution->factus_id,
                'payment_method_code' => $paymentMethod->factus_id,
                'observation' => $invoiceData['detail'] ?? '',
                'payment_form' => $paymentForm->factus_id,
                'establishment' => $establishment,
                'provider' => $customerBlock,
                'items' => $items,
            ];
            if (!empty($allowanceCharges)) {
                $payload['allowance_charges'] = $allowanceCharges;
            }
            return $payload;
    }
}
