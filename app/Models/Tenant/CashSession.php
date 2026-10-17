<?php

namespace App\Models\Tenant;

use App\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashSession extends Model
{
    use UsesTenantConnection, SoftDeletes;

    protected $table = 'cash_sessions';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    protected function casts(): array
    {
        return [
            'opening_amount' => 'decimal:2',
            'expected_amount' => 'decimal:2',
            'closing_amount' => 'decimal:2',
            'difference_amount' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function register()
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id', 'codmov');
    }

    public function movements()
    {
        return $this->hasMany(CashMovement::class, 'cash_session_id', 'codmov');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'cash_session_id', 'codmov');
    }
}
