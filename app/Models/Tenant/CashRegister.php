<?php

namespace App\Models\Tenant;

use App\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashRegister extends Model
{
    use UsesTenantConnection, SoftDeletes;

    protected $table = 'cash_registers';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    public function printer()
    {
        return $this->belongsTo(Printer::class, 'printer_id', 'codmov');
    }

    public function sessions()
    {
        return $this->hasMany(CashSession::class, 'cash_register_id', 'codmov');
    }
}
