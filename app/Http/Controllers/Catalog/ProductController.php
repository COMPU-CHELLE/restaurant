<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Concerns\CrudResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\ProductImagesRequest;
use App\Http\Requests\Catalog\ProductPriceRequest;
use App\Http\Requests\Catalog\ProductRequest;
use App\Models\Tenant\Companie;
use App\Services\Catalog\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    use CrudResponses;

    public function __construct(private ProductService $service)
    {
    }

    public function index(Request $request): Response
    {
        $filters = $this->tableFilters($request) + ['category' => $request->integer('category') ?: null];

        return Inertia::render('catalogo/productos', [
            'products' => $this->service->paginateRows($filters, $filters['per_page']),
            'filters' => $filters,
            'options' => [
                'categories' => $this->service->categoryOptions(),
                'currency' => Companie::query()->value('currency') ?: 'COP',
            ],
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        return $this->done($this->service->store($request->validated()));
    }

    public function update(ProductRequest $request, int $product): RedirectResponse
    {
        return $this->done($this->service->update($this->service->find($product), $request->validated()));
    }

    public function toggle(int $product): RedirectResponse
    {
        return $this->done($this->service->toggleState($this->service->find($product)));
    }

    public function destroy(int $product): RedirectResponse
    {
        return $this->done($this->service->destroy($this->service->find($product)));
    }

    /** Historial de precios e imágenes para los diálogos (JSON, se pide al abrirlos). */
    public function detail(int $product): JsonResponse
    {
        return response()->json($this->service->detail($this->service->find($product)));
    }

    public function addPrice(ProductPriceRequest $request, int $product): RedirectResponse
    {
        $data = $request->validated();

        return $this->done($this->service->changePrice($this->service->find($product), $data['price_type'], (float) $data['price']));
    }

    public function uploadImages(ProductImagesRequest $request, int $product): RedirectResponse
    {
        return $this->done($this->service->addImages($this->service->find($product), $request->file('images', [])));
    }

    public function primaryImage(int $product, int $image): RedirectResponse
    {
        return $this->done($this->service->setPrimaryImage($this->service->find($product), $image));
    }

    public function destroyImage(int $product, int $image): RedirectResponse
    {
        return $this->done($this->service->deleteImage($this->service->find($product), $image));
    }
}
