<?php

namespace App\Services\Factus;

use App\Models\Support\FactusService as FactusConfig;
use App\Models\Tenant\Companie;
use App\Models\Tenant\FactusDocumentsRequest;
use App\Models\Tenant\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/** Adapta una orden de Restaurant a una factura electrónica Factus V2. */
class RestaurantOrderFactusService
{
    public function __construct(private FactusAuthService $auth) {}

    public function emit(Order $order, int $companyId): array
    {
        $order->loadMissing(['items.product', 'contact', 'payments']);
        if ($order->status !== 'PAID') {
            throw new RuntimeException('Solo se puede emitir una factura para una orden pagada.');
        }
        $existing = FactusDocumentsRequest::query()
            ->where('document_table', 'orders')->where('document_id', $order->codmov)
            ->where('status', 'success')->latest('codmov')->first();
        if ($existing) return ['already_emitted' => true, 'document' => $existing];

        $config = FactusConfig::query()->where('codemp', $companyId)
            ->where('environment', config('factus.environment', 'sandbox'))
            ->where('active', true)->first();
        if (!$config) throw new RuntimeException('No hay configuración Factus activa para esta empresa y ambiente.');
        if (!$config->numbering_range_id) throw new RuntimeException('Configura el rango de numeración de Factus antes de emitir.');
        $company = Companie::query()->first();
        $customer = $order->contact;
        if (!$customer) throw new RuntimeException('La orden debe tener un cliente con identificación para emitir factura electrónica.');
        if (!$config->municipality_code) throw new RuntimeException('Configura el código de municipio DIAN de la empresa en Factus.');
        if (!$order->items->count()) throw new RuntimeException('La orden no tiene productos para facturar.');

        $reference = 'REST-' . $companyId . '-' . $order->codmov;
        $rate = (float) ($company?->tax_percentage ?? 0);
        $items = $order->items->map(function ($item) use ($config, $rate) {
            $product = $item->product;
            return [
                'code_reference' => (string) ($product?->code ?: $item->product_id),
                'name' => mb_substr((string) $item->product_name, 0, 250),
                'quantity' => number_format((float) $item->quantity, 3, '.', ''),
                'discount_rate' => '0.00',
                'price' => number_format((float) $item->unit_price, 2, '.', ''),
                'unit_measure_id' => (string) ($config->unit_measure_id ?: '70'),
                'standard_code_id' => '1',
                'is_excluded' => '0',
                'tax_rate' => number_format($rate, 2, '.', ''),
                'taxes' => $rate > 0 ? [['code' => (string) ($config->tax_code ?: '01'), 'rate' => number_format($rate, 2, '.', '')]] : [],
            ];
        })->values()->all();

        $documentType = FactusReferences::documentCode($customer->document_type);
        $legal = strtoupper((string) $customer->document_type) === 'NIT' ? '1' : '2';
        $customerName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
        $customerPayload = [
            'identification_document_code' => $documentType,
            'identification' => (string) $customer->document_number,
            'company' => (string) ($customer->business_name ?: ($customerName ?: 'Consumidor')),
            'trade_name' => (string) ($customer->business_name ?: ($customerName ?: 'Consumidor')),
            'names' => (string) ($customerName ?: $customer->business_name ?: 'Consumidor'),
            'address' => (string) ($customer->address ?: ($company?->address ?: '')),
            'email' => (string) ($customer->email ?: ($company?->email ?: '')),
            'phone' => (string) ($customer->phone ?: $customer->mobile ?: ''),
            'legal_organization_code' => $legal,
            'tribute_id' => '21',
            'municipality_code' => (string) $config->municipality_code,
        ];
        $payload = [
            'reference_code' => $reference,
            'document' => '01',
            'numbering_range_id' => (int) $config->numbering_range_id,
            'operation_type' => '10',
            'observation' => 'Orden Restaurant ' . $order->code,
            'payment_form' => (string) ($config->payment_form ?: '1'),
            'payment_method_code' => (string) ($config->payment_method_code ?: '10'),
            'payment_details' => [[
                'payment_form' => (string) ($config->payment_form ?: '1'),
                'payment_method_code' => (string) ($config->payment_method_code ?: '10'),
                'reference_code' => $reference,
                'amount' => number_format((float) $order->total, 2, '.', ''),
            ]],
            'customer' => $customerPayload,
            'items' => $items,
        ];
        $endpoint = rtrim($config->base_url, '/') . '/v2/bills/validate';
        $requestRecord = FactusDocumentsRequest::query()->create([
            'provider' => 'factus', 'document_type' => 'Invoice', 'document_table' => 'orders',
            'document_id' => $order->codmov, 'reference_code' => $reference,
            'status' => 'pending', 'payload' => $payload, 'environment' => $config->environment,
            'endpoint' => '/v2/bills/validate', 'host' => request()?->ip(),
            'codope' => (string) (auth()->id() ?? 'SYSTEM'), 'basdat' => 'restaurant',
        ]);
        try {
            $response = Http::withToken($this->auth->getToken($config))->acceptJson()->asJson()->timeout(45)->post($endpoint, $payload);
            $body = $response->json() ?? ['raw' => $response->body()];
            $data = $body['data'] ?? [];
            $requestRecord->forceFill([
                'status' => $response->successful() ? 'success' : 'error',
                'response' => $body, 'http_code' => $response->status(),
                'document_id_factus' => $data['bill']['id'] ?? $data['id'] ?? null,
                'document_number' => $data['bill']['number'] ?? $data['number'] ?? null,
                'cufe' => $data['bill']['cufe'] ?? $data['cufe'] ?? null,
                'qr' => $data['bill']['qr'] ?? $data['qr'] ?? null,
                'public_url' => $data['bill']['public_url'] ?? $data['public_url'] ?? null,
                'qr_image' => $data['bill']['qr_image'] ?? $data['qr_image'] ?? null,
                'error' => $response->successful() ? null : mb_substr(json_encode($body, JSON_UNESCAPED_UNICODE) ?: 'Factus error', 0, 60000),
            ])->save();
            if (!$response->successful()) throw new RuntimeException('Factus rechazó la factura (HTTP '.$response->status().'): '.mb_substr(json_encode($body, JSON_UNESCAPED_UNICODE) ?: '', 0, 1500));
            return ['already_emitted' => false, 'document' => $requestRecord->fresh()];
        } catch (Throwable $e) {
            if ($requestRecord->status === 'pending') $requestRecord->forceFill(['status'=>'error','error'=>mb_substr($e->getMessage(),0,60000)])->save();
            Log::warning('Factus invoice emission failed', ['order_id'=>$order->codmov, 'company_id'=>$companyId, 'message'=>$e->getMessage()]);
            throw $e;
        }
    }
}
