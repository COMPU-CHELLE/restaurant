<?php

namespace App\Services\Catalog;

use App\Models\Tenant\Category;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductImage;
use App\Models\Tenant\ProductPrice;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Productos de la empresa activa: datos, historial de precios (SALE / COST) e imágenes.
 * product.sale_price / cost_price guardan el precio VIGENTE; product_prices guarda el historial.
 */
class ProductService extends BaseService
{
    public const MAX_IMAGES = 6;

    protected string $model = Product::class;

    protected string $label = 'Producto';

    protected array $searchable = ['code', 'barcode', 'name', 'detail'];

    protected array $sortable = ['codmov', 'code', 'name', 'sale_price', 'state'];

    protected array $fields = ['category_id', 'code', 'barcode', 'name', 'detail', 'product_type'];

    protected bool $audited = true;

    public function find(int $id): Product
    {
        return Product::query()->findOrFail($id);
    }

    public function categoryOptions(): array
    {
        return Category::query()->where('state', 1)->orderBy('name')->get(['codmov', 'code', 'name'])
            ->map(fn ($c) => ['id' => (int) $c->codmov, 'code' => $c->code, 'name' => $c->name])->all();
    }

    protected function query(): Builder
    {
        return parent::query()->with(['category:codmov,name', 'images']);
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        parent::applyFilters($query, $filters);

        if (! empty($filters['category'])) {
            $query->where('category_id', (int) $filters['category']);
        }
    }

    public function paginateRows(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->paginate($filters, $perPage)->through(function (Product $product) {
            $cover = $product->images->sortByDesc('is_primary')->sortBy('sort_order')->sortByDesc('is_primary')->first();

            return [
                'id' => (int) $product->codmov,
                'code' => $product->code,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'detail' => $product->detail,
                'category_id' => (int) $product->category_id,
                'category' => $product->category?->name,
                'sale_price' => (float) $product->sale_price,
                'cost_price' => (float) $product->cost_price,
                'product_type' => $product->product_type,
                'state' => (int) $product->state,
                'image_url' => $cover ? $this->imageUrl($cover) : null,
                'images_count' => $product->images->count(),
            ];
        });
    }

    public function store(array $payload): array
    {
        return $this->transaction(function () use ($payload) {
            $product = new Product();
            $product->forceFill($this->stamp($this->onlyFields($payload), true) + ['sale_price' => 0, 'cost_price' => 0, 'state' => 1])->save();

            $this->recordPrice($product, 'SALE', (float) ($payload['sale_price'] ?? 0), true);
            $this->recordPrice($product, 'COST', (float) ($payload['cost_price'] ?? 0), true);

            return ['data' => $product, 'flash' => $this->toast('Producto creado correctamente.')];
        });
    }

    public function update(Model $record, array $payload): array
    {
        /** @var Product $record */
        return $this->transaction(function () use ($record, $payload) {
            $record->forceFill($this->stamp($this->onlyFields($payload), false))->save();

            // Un cambio de precio desde el formulario también queda en el historial.
            foreach (['SALE' => 'sale_price', 'COST' => 'cost_price'] as $type => $field) {
                if (array_key_exists($field, $payload) && $payload[$field] !== null && $payload[$field] !== '') {
                    $this->recordPrice($record, $type, (float) $payload[$field]);
                }
            }

            return ['data' => $record, 'flash' => $this->toast('Producto actualizado correctamente.')];
        });
    }

    public function destroy(Product $product): array
    {
        if (OrderItem::query()->where('product_id', $product->codmov)->exists()) {
            return ['data' => $product, 'flash' => $this->toast('El producto ya se vendió: no se puede eliminar, solo inactivar.', 'error')];
        }

        $product->delete();

        return ['data' => $product, 'flash' => $this->toast('Producto eliminado.')];
    }

    /** Cambia el precio vigente (SALE o COST) y lo registra en el historial. */
    public function changePrice(Product $product, string $type, float $price): array
    {
        $this->transaction(fn () => $this->recordPrice($product, $type, $price));

        return ['data' => $product, 'flash' => $this->toast($type === 'SALE' ? 'Precio de venta actualizado.' : 'Costo actualizado.')];
    }

    protected function recordPrice(Product $product, string $type, float $price, bool $initial = false): void
    {
        $column = $type === 'SALE' ? 'sale_price' : 'cost_price';

        if ($initial ? $price <= 0 : round((float) $product->{$column}, 2) === round($price, 2)) {
            return; // nada que registrar
        }

        $now = now();

        ProductPrice::query()
            ->where('product_id', $product->codmov)->where('price_type', $type)->where('active', true)
            ->update(['active' => false, 'effective_to' => $now]);

        ProductPrice::query()->create([
            'product_id' => $product->codmov, 'price_type' => $type, 'price' => $price,
            'effective_from' => $now, 'active' => true,
        ]);

        $product->forceFill([$column => $price])->save();
    }

    /** Historial de precios e imágenes (lo que muestran los diálogos). */
    public function detail(Product $product): array
    {
        return [
            'prices' => ProductPrice::query()->where('product_id', $product->codmov)->orderByDesc('codmov')->limit(50)->get()
                ->map(fn ($p) => [
                    'id' => (int) $p->codmov, 'type' => $p->price_type, 'price' => (float) $p->price, 'active' => (bool) $p->active,
                    'from' => $p->effective_from?->toIso8601String(), 'to' => $p->effective_to?->toIso8601String(),
                ])->all(),
            'images' => ProductImage::query()->where('product_id', $product->codmov)->orderByDesc('is_primary')->orderBy('sort_order')->get()
                ->map(fn ($i) => ['id' => (int) $i->codmov, 'url' => $this->imageUrl($i), 'primary' => (bool) $i->is_primary])->all(),
        ];
    }

    /** @param  array<int, \Illuminate\Http\UploadedFile>  $files */
    public function addImages(Product $product, array $files): array
    {
        $existing = ProductImage::query()->where('product_id', $product->codmov)->count();

        if ($existing + count($files) > self::MAX_IMAGES) {
            return ['data' => $product, 'flash' => $this->toast('Máximo ' . self::MAX_IMAGES . ' imágenes por producto.', 'error')];
        }

        $hasPrimary = ProductImage::query()->where('product_id', $product->codmov)->where('is_primary', true)->exists();
        $folder = 'tenants/' . (int) session('company_id') . '/products/' . $product->codmov;

        foreach (array_values($files) as $i => $file) {
            // El nombre lo genera el servidor (nunca el del archivo subido).
            $path = $file->storeAs($folder, Str::uuid() . '.' . $file->extension(), 'public');

            ProductImage::query()->create([
                'product_id' => $product->codmov, 'image' => $path, 'alt_text' => $product->name,
                'sort_order' => $existing + $i, 'is_primary' => ! $hasPrimary && $i === 0,
            ]);
        }

        return ['data' => $product, 'flash' => $this->toast(count($files) === 1 ? 'Imagen agregada.' : 'Imágenes agregadas.')];
    }

    public function setPrimaryImage(Product $product, int $imageId): array
    {
        $image = ProductImage::query()->where('product_id', $product->codmov)->findOrFail($imageId);

        $this->transaction(function () use ($product, $image) {
            ProductImage::query()->where('product_id', $product->codmov)->update(['is_primary' => false]);
            $image->forceFill(['is_primary' => true])->save();
        });

        return ['data' => $product, 'flash' => $this->toast('Imagen principal actualizada.')];
    }

    public function deleteImage(Product $product, int $imageId): array
    {
        $image = ProductImage::query()->where('product_id', $product->codmov)->findOrFail($imageId);

        $this->transaction(function () use ($product, $image) {
            Storage::disk('public')->delete($image->image);
            $wasPrimary = (bool) $image->is_primary;
            $image->forceDelete();

            if ($wasPrimary) {
                ProductImage::query()->where('product_id', $product->codmov)->orderBy('sort_order')->first()
                    ?->forceFill(['is_primary' => true])->save();
            }
        });

        return ['data' => $product, 'flash' => $this->toast('Imagen eliminada.')];
    }

    protected function imageUrl(ProductImage $image): string
    {
        return Storage::disk('public')->url($image->image);
    }
}
