<?php

namespace App\Models\Tenant;

use App\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use UsesTenantConnection;

    protected $table = 'inventory_movements';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'codmov');
    }
}
