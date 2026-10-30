<?php

namespace App\Models\Tenant;

use App\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class CatalogItem extends Model
{
    use UsesTenantConnection;

    protected $table = 'catalog_items';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    protected function casts(): array
    {
        return ['extra' => 'array', 'imported_at' => 'datetime'];
    }
}
