<?php

namespace App\Models\Tenant;

use App\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductImage extends Model
{
    use UsesTenantConnection, SoftDeletes;

    protected $table = 'product_images';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'codmov');
    }
}
