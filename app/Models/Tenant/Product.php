<?php

namespace App\Models\Tenant;

use App\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use UsesTenantConnection, SoftDeletes;

    protected $table = 'products';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    protected function casts(): array
    {
        return [
            'sale_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'codmov');
    }

    public function prices()
    {
        return $this->hasMany(ProductPrice::class, 'product_id', 'codmov');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id', 'codmov');
    }
}
