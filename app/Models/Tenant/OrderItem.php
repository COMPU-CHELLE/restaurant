<?php

namespace App\Models\Tenant;

use App\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use UsesTenantConnection, SoftDeletes;

    protected $table = 'order_items';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'codmov');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'codmov');
    }
}
