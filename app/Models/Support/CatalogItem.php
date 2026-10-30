<?php

namespace App\Models\Support;

use App\Traits\UsesSupportConnection;
use Illuminate\Database\Eloquent\Model;

class CatalogItem extends Model
{
    use UsesSupportConnection;

    protected $table = 'catalog_items';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    protected function casts(): array
    {
        return ['extra' => 'array'];
    }
}
