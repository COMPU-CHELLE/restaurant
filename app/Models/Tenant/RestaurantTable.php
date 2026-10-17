<?php

namespace App\Models\Tenant;

use App\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RestaurantTable extends Model
{
    use UsesTenantConnection, SoftDeletes;

    protected $table = 'restaurant_tables';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id', 'codmov');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'restaurant_table_id', 'codmov');
    }
}
