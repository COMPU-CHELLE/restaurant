<?php

namespace App\Services\Factus;

use App\Models\Support\FactusPayService as PayConfig;
use App\Models\Tenant\FactusPayCollection;
use App\Models\Tenant\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Crea y sincroniza recaudos de Factus Pay. IMPORTANTE: un recaudo "paid" NO registra el pago en caja;
 * eso lo hace la conciliación (payments + cierre de orden) en una transacción aparte.
 */
class FactusPayCollectionService
{
    public function __construct(private FactusPayService $pay) {}

    public function configFor(int $companyId): PayConfig
    {
        $cfg = PayConfig::query()->where('codemp', $companyId)
            ->where('environment', config('factus.pay_environment', 'sandbox'))
            ->where('active', true)->first();

        if (! $cfg) {
            throw new RuntimeException('Esta empresa no tiene Factus Pay activo. Pide a soporte que lo configure.');
        }

        return $cfg;
    }

    /** Idempotente: si la orden ya tiene un recaudo vigente lo devuelve en vez de crear otro. */
    public function create(Order $order, int $companyId, string $method = 'QR'): FactusPayCollection
    {
        if ($order->status === 'CANCELLED' || $order->status === 'PAID') {
            throw new RuntimeException('Solo se puede cobrar una orden abierta.');
        }

        $current = FactusPayCollection::query()->where('order_id', $order->codmov)
            ->where('status', FactusPayCollection::PENDING)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latest('codmov')->first();

        if ($current) {
            return $current;
        }

        $cfg = $this->configFor($companyId);

        // Un reintento (recaudo anterior vencido o fallido) necesita una referencia nueva.
        $attempt = FactusPayCollection::query()->where('order_id', $order->codmov)->count();
        $reference = 'REST-' . $companyId . '-' . $order->codmov . ($attempt > 0 ? '-' . ($attempt + 1) : '');

        $data = $this->pay->createCollection($cfg, $reference, (float) $order->total, $method);

        return DB::connection('tenant')->transaction(fn () => FactusPayCollection::query()->create([
            'order_id' => $order->codmov,
            'reference_code' => $reference,
            'factus_collection_id' => $data['id'],
            'amount' => $order->total,
            'method' => $method,
            'status' => $data['status'],
            'qr' => $data['qr'],
            'qr_image' => $data['qr_image'],
            'expires_at' => $this->date($data['expires_at']),
            'paid_at' => $data['status'] === 'paid' ? ($this->date($data['paid_at']) ?? now()) : null,
            'environment' => $cfg->environment,
            'response' => $data['raw'],
            'host' => request()?->ip(),
            'codope' => (string) (auth()->user()?->code ?? 'SYSTEM'),
            'basdat' => 'restaurant',
        ]));
    }

    /** Consulta el estado en Factus y lo guarda. No toca payments ni la orden. */
    public function refresh(FactusPayCollection $collection, int $companyId): FactusPayCollection
    {
        if ($collection->isFinal()) {
            return $collection;
        }

        $data = $this->pay->showCollection($this->configFor($companyId), $collection->reference_code);

        $collection->forceFill([
            'status' => $data['status'],
            'paid_at' => $data['status'] === 'paid' ? ($collection->paid_at ?? $this->date($data['paid_at']) ?? now()) : $collection->paid_at,
            'last_checked_at' => now(),
            'response' => $data['raw'],
        ])->save();

        return $collection;
    }

    private function date(?string $value): ?Carbon
    {
        return $value ? Carbon::parse($value) : null;
    }
}
