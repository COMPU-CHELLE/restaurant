<?php

namespace App\Http\Controllers\Catalog;

use App\Services\Catalog\CatalogService;
use App\Services\Catalog\SupportCatalogService;

/** Soporte → Parámetros: catálogos globales por defecto. */
class SupportCatalogController extends BaseCatalogController
{
    public function __construct(private SupportCatalogService $catalogs)
    {
    }

    protected function service(): CatalogService
    {
        return $this->catalogs;
    }

    protected function scope(): string
    {
        return 'soporte';
    }
}
