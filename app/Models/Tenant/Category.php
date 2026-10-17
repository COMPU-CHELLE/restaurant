<?php

namespace App\Models\Tenant;

use App\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use UsesTenantConnection, SoftDeletes;

    protected $table = 'categories';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id', 'codmov');
    }
}
