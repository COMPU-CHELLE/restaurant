<?php

namespace App\Models\Tenant;

use App\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use UsesTenantConnection, SoftDeletes;

    protected $table = 'payments';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'surcharge_amount' => 'decimal:2',
            'total_charged' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'codmov');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'codmov');
    }

    public function session()
    {
        return $this->belongsTo(CashSession::class, 'cash_session_id', 'codmov');
    }
}
