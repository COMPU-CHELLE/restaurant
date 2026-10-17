<?php

namespace App\Models\Tenant;

use App\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientSupplier extends Model
{
    use UsesTenantConnection, SoftDeletes;

    protected $table = 'clients_suppliers';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    protected function casts(): array
    {
        return [
            'customer' => 'boolean',
            'supplier' => 'boolean',
        ];
    }
}
