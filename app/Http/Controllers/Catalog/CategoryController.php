<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Concerns\CrudResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\CategoryRequest;
use App\Services\Catalog\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    use CrudResponses;

    public function __construct(private CategoryService $service)
    {
    }

    public function index(Request $request): Response
    {
        $filters = $this->tableFilters($request);

        return Inertia::render('catalogo/categorias', [
            'categories' => $this->service->paginateRows($filters, $filters['per_page']),
            'filters' => $filters,
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        return $this->done($this->service->store($request->validated()));
    }

    // Los modelos del tenant se cargan aquí por id (ya con la base de la empresa activa).
    public function update(CategoryRequest $request, int $category): RedirectResponse
    {
        return $this->done($this->service->update($this->service->find($category), $request->validated()));
    }

    public function toggle(int $category): RedirectResponse
    {
        return $this->done($this->service->toggleState($this->service->find($category)));
    }

    public function destroy(int $category): RedirectResponse
    {
        return $this->done($this->service->destroy($this->service->find($category)));
    }
}
