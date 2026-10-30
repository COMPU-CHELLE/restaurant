<?php

namespace App\Services\Catalog;

use App\Models\Support\CatalogRecord;

class SupportCatalogService extends CatalogService
{
    protected string $model = CatalogRecord::class;

    protected function scopeName(): string
    {
        return 'support';
    }
}
