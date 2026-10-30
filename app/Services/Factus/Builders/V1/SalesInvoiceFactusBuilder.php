<?php

namespace App\Services\Factus\Builders\V1;

use App\Models\Tenant\Prefix;
use App\Models\Tenant\ClientSupplier;
use App\Models\Tenant\PaymentForm;
use Carbon\Carbon;

class SalesInvoiceFactusBuilder
{
    /**
     * Construye el payload para Factus a partir de los datos de la factura.
     *
     * @param array $invoiceData
     * @param \Illuminate\Support\Collection|array $details
     * @param Prefix $prefix
     * @param ClientSupplier $customer
     * @param mixed $branch
     * @return array
     */
    public function build(
        array $invoiceData,
        $details,
        Prefix $prefix,
        ClientSupplier $customer,
        $branch
    ): array {
        //dd($invoiceData);
        $paymentForm = PaymentForm::where('codmov', $invoiceData['tippag'])->first();
        $paymentMethod = PaymentForm::where('codmov', $invoiceData['forpag'])->first();
        $establishment = [
            'name' => $branch->name ?? '',
            'address' => $branch->address ?? '',
            'phone_number' => $branch->phone ?? '',
            // Si la sede no tiene email, usar el global de la empresa si está en $invoiceData['company_email']
            'email' => $branch->email ?? $invoiceData['company_email'] ?? '',
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

        $baseDate = Carbon::parse($invoiceData['fecope']);
        $days = (int) ($invoiceData['days'] ?? 0);
        $paymentDueDate = $baseDate->addDays($days)->format('Y-m-d');
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
                'price' => (float) ($item['valund'] ?? 0),
                'tax_rate' => number_format($item['poriva'] ?? 0, 2, '.', ''),
                // undmed llega como código de texto (ej. "UND", "KG") — se traduce
                // contra la tabla units para obtener el factus_id real.
                'unit_measure_id'   => \App\Services\Factus\FactusItemMapper::unitMeasureId($item, $unitFactusIds),
                'standard_code_id'  => $item['standard_code_id'] ?? 1,
                'is_excluded'       => $isExcluded,
                'tribute_id'        => $item['tribute_id'] ?? 1,
                'withholding_taxes' => $item['withholding_taxes'] ?? [],
            ];
        })->values()->toArray();

        $allowanceCharges = $invoiceData['allowance_charges'] ?? [];

            $payload = [
                'document' => '01',
                'numbering_range_id' => $prefix->activeResolution->factus_id,
                'reference_code' => trim(($invoiceData['prefij'] ?? '') . ($invoiceData['number'] ?? '')),
                'observation' => $invoiceData['detail'] ?? '',
                'payment_form' => $paymentForm->factus_id,
                'payment_method_code' => $paymentMethod->factus_id,
                'establishment' => $establishment,
                'customer' => $customerBlock,
                'items' => $items,
                'payment_due_date' => $paymentDueDate,
            ];
            if (!empty($allowanceCharges)) {
                $payload['allowance_charges'] = $allowanceCharges;
            }
            return $payload;
    }
}
