<?php

namespace App\Models\Tenant;

use App\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashMovement extends Model
{
    use UsesTenantConnection, SoftDeletes;

    protected $table = 'cash_movements';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function session()
    {
        return $this->belongsTo(CashSession::class, 'cash_session_id', 'codmov');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'codmov');
    }
}
