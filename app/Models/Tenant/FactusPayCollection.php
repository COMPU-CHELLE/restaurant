<?php

namespace App\Models\Tenant;

use App\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

/** Recaudo (QR / Bre-B) generado en Factus Pay para una orden. Pagado en Factus != pagado en caja. */
class FactusPayCollection extends Model
{
    use UsesTenantConnection;

    public const PENDING = 'pending';
    public const PAID = 'paid';

    protected $table = 'factus_pay_collections';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'response' => 'array',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'last_checked_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'codmov');
    }

    public function isFinal(): bool
    {
        return in_array($this->status, ['paid', 'expired', 'failed', 'cancelled'], true);
    }
}
