<?php

namespace App\Models\Tenant;

use App\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

/** Unidades de medida de la empresa (tabla units). factus_id = V1, code_fe = V2. */
class Unit extends Model
{
    use UsesTenantConnection;

    protected $table = 'units';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    protected function casts(): array
    {
        return ['factor' => 'decimal:4'];
    }
}
