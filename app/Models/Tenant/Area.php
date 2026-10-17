<?php

namespace App\Models\Tenant;

use App\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Area extends Model
{
    use UsesTenantConnection, SoftDeletes;

    protected $table = 'areas';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    public function tables()
    {
        return $this->hasMany(RestaurantTable::class, 'area_id', 'codmov');
    }
}
