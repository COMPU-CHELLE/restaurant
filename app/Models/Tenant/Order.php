<?php

namespace App\Models\Tenant;

use App\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use UsesTenantConnection, SoftDeletes;

    protected $table = 'orders';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'ordered_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function restaurantTable()
    {
        return $this->belongsTo(RestaurantTable::class, 'restaurant_table_id', 'codmov');
    }

    public function session()
    {
        return $this->belongsTo(CashSession::class, 'cash_session_id', 'codmov');
    }

    public function contact()
    {
        return $this->belongsTo(ClientSupplier::class, 'contact_id', 'codmov');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'codmov');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'order_id', 'codmov');
    }
}
