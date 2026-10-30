<?php

namespace App\Services\Factus\Builders\V2;

class SalesInvoiceFactusBuilder
{
    /**
     * Genera el payload para Factus V2 a partir de una factura de venta
     * @param array $invoiceData
     * @param \Illuminate\Support\Collection|array $details
     * @param $prefix
     * @param $customer
     * @param $branch
     * @param array $extra (opcional)
     * @return array
     */
    public function build($invoiceData, $details, $prefix, $customer, $branch, $extra = [])
    {
        // --- ESTABLECIMIENTO (empresa/sucursal) ---
        $establishment = [
            'name' => $branch->name ?? '',
            'address' => $branch->address ?? '',
            'phone_number' => $branch->phone ?? '',
            'email' => $branch->email ?? $invoiceData['company_email'] ?? '',
            'municipality_code' => $branch->subzone->factus_id ?? null,
        ];

        // --- CLIENTE ---
        $customerBlock = [
            'identification_document_code' => $customer->tipdoc->code_fe ?? '',
            'identification' => $customer->nitnum,
            'dv' => $customer->nitdig,
            'company' => $customer->razsoc ?? '',
            'trade_name' => $customer->razsoc ?? '',
            'names' => trim(($customer->nomuno ?? '') . ' ' . ($customer->nomdos ?? '') . ' ' . ($customer->apeuno ?? '') . ' ' . ($customer->apedos ?? '')),
            'address' => $customer->address ?? '',
            'email' => $customer->email ?? '',
            'phone' => $customer->phone ?? '',
            'legal_organization_code' => $customer->legal_organization_id ?? '1',
            'tribute_code' => $customer->tribute_id ?? 'ZZ',
            'municipality_code' => $customer->subzone->factus_id ?? null,
        ];

        // --- ITEMS ---
        $items = collect($details)->map(function ($item) {
            if (is_object($item)) {
                $item = (array) $item;
            }
            $taxes = [];
            if (isset($item['poriva']) && (float)$item['poriva'] > 0) {
                $taxes[] = [
                    'code' => '01',
                    'rate' => number_format($item['poriva'], 2, '.', ''),
                ];
            }
            return [
                'code_reference' => $item['refere'] ?? '',
                'name' => $item['name'] ?? '',
                'quantity' => number_format((float)($item['cantid'] ?? 0), 2, '.', ''),
                'discount_rate' => number_format(\App\Services\Factus\FactusItemMapper::discountRate($item), 2, '.', ''),
                'price' => number_format((float)($item['valund'] ?? 0), 2, '.', ''),
                'unit_measure_code' => $item['undmed'] ?? '94',
                'standard_code' => $item['standard_code_id'] ?? '999',
                'taxes' => $taxes,
            ];
        })->values()->toArray();

        // --- PAYMENT DETAILS (ejemplo simple, adaptar según tu lógica) ---
        $paymentDetails = $invoiceData['payment_details'] ?? [
            [
                'payment_form' => '1',
                'payment_method_code' => $invoiceData['tippag'] ?? '10',
                'reference_code' => $invoiceData['reference_code'] ?? '',
                'amount' => number_format($invoiceData['valpag'] ?? 0, 2, '.', ''),
                'due_date' => $invoiceData['payment_due_date'] ?? null,
            ]
        ];

        // --- PREPAYMENT DETAILS (opcional) ---
        $prepaymentDetails = $invoiceData['prepayment_details'] ?? [];

        // --- ARMADO FINAL ---
        $payload = [
            'reference_code' => $invoiceData['reference_code'] ?? trim(($invoiceData['prefij'] ?? '') . ($invoiceData['number'] ?? '')),
            'document' => $invoiceData['document'] ?? '01',
            'numbering_range_id' => $invoiceData['numbering_range_id'] ?? ($prefix->id_factus ?? null),
            'operation_type' => $invoiceData['operation_type'] ?? '10',
            'observation' => $invoiceData['observation'] ?? ($invoiceData['detail'] ?? ''),
            'establishment' => $establishment,
            'customer' => $customerBlock,
            'items' => $items,
            'payment_details' => $paymentDetails,
            'prepayment_details' => $prepaymentDetails,
        ];

        // Permite agregar allowance_charges, taxes, withholding_taxes, totals, related_documents, etc.
        if (!empty($extra)) {
            $payload = array_merge($payload, $extra);
        }

        return $payload;
    }
}
