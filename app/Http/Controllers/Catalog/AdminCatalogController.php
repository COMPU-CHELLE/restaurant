<?php

namespace App\Http\Controllers\Catalog;

use App\Services\Catalog\CatalogService;
use App\Services\Catalog\TenantCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Administración → Parámetros: los de la empresa activa, con importación desde soporte. */
class AdminCatalogController extends BaseCatalogController
{
    public function __construct(private TenantCatalogService $catalogs)
    {
    }

    protected function service(): CatalogService
    {
        return $this->catalogs;
    }

    protected function scope(): string
    {
        return 'admin';
    }

    protected function extras(string $type): array
    {
        return ['importable' => $this->catalogs->importable($type)];
    }

    public function import(Request $request, string $type): RedirectResponse
    {
        return $this->done($this->catalogs->import($type, $request->boolean('update')));
    }

    public function importAll(Request $request): RedirectResponse
    {
        return $this->done($this->catalogs->importAll($request->boolean('update')));
    }
}
