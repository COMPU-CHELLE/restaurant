<?php

namespace App\Services\Factus\Builders\V2;

class PurchaseNoteFactusBuilder
{
    /**
     * Genera el payload para Factus V2 a partir de una nota de compra
     * @param array $noteData
     * @param \Illuminate\Support\Collection|array $details
     * @param $prefix
     * @param $provider
     * @param array $extra (opcional)
     * @return array
     */
    public function build($noteData, $details, $prefix, $provider, $extra = [])
    {
        // --- PROVEEDOR ---
        $providerBlock = [
            'identification_document_code' => $provider->tipdoc->code_fe ?? '',
            'identification' => $provider->nitnum,
            'dv' => $provider->nitdig,
            'company' => $provider->razsoc ?? '',
            'trade_name' => $provider->razsoc ?? '',
            'names' => trim(($provider->nomuno ?? '') . ' ' . ($provider->nomdos ?? '') . ' ' . ($provider->apeuno ?? '') . ' ' . ($provider->apedos ?? '')),
            'address' => $provider->address ?? '',
            'email' => $provider->email ?? '',
            'phone' => $provider->phone ?? '',
            'legal_organization_code' => $provider->legal_organization_id ?? '1',
            'tribute_code' => $provider->tribute_id ?? 'ZZ',
            'municipality_code' => $provider->subzone->factus_id ?? null,
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
                'price' => number_format(\App\Services\Factus\FactusItemMapper::priceWithTax($item), 2, '.', ''),
                'unit_measure_code' => $item['undmed'] ?? '94',
                'standard_code' => $item['standard_code_id'] ?? '999',
                'taxes' => $taxes,
            ];
        })->values()->toArray();

        // --- PAYMENT DETAILS (ejemplo simple, adaptar según tu lógica) ---
        $paymentDetails = $noteData['payment_details'] ?? [
            [
                'payment_form' => '1',
                'payment_method_code' => $noteData['tippag'] ?? '10',
                'reference_code' => $noteData['reference_code'] ?? '',
                'amount' => number_format($noteData['valpag'] ?? 0, 2, '.', ''),
                'due_date' => $noteData['payment_due_date'] ?? null,
            ]
        ];

        // --- PREPAYMENT DETAILS (opcional) ---
        $prepaymentDetails = $noteData['prepayment_details'] ?? [];

        // --- ARMADO FINAL ---
        $payload = [
            'reference_code' => $noteData['reference_code'] ?? trim(($noteData['prefij'] ?? '') . ($noteData['number'] ?? '')),
            'document' => $noteData['document'] ?? '04',
            'numbering_range_id' => $noteData['numbering_range_id'] ?? ($prefix->id_factus ?? null),
            'operation_type' => $noteData['operation_type'] ?? '10',
            'observation' => $noteData['observation'] ?? ($noteData['detail'] ?? ''),
            'provider' => $providerBlock,
            'items' => $items,
            'payment_details' => $paymentDetails,
            'prepayment_details' => $prepaymentDetails,
        ];

        if (!empty($extra)) {
            $payload = array_merge($payload, $extra);
        }

        return $payload;
    }
}
