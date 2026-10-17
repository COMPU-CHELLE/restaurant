<?php

namespace App\Models\Tenant;

use App\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Companie extends Model
{
    use UsesTenantConnection, SoftDeletes;

    protected $table = 'companie';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    protected function casts(): array
    {
        return [
            'tax_percentage' => 'decimal:2',
        ];
    }
}
