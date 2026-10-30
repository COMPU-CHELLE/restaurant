<?php

namespace App\Services\Catalog;

use App\Models\Tenant\Category;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/** Categorías de productos de la empresa activa (base tenant). */
class CategoryService extends BaseService
{
    protected string $model = Category::class;

    protected string $label = 'Categoría';

    protected array $searchable = ['code', 'name', 'detail'];

    protected array $sortable = ['codmov', 'code', 'name', 'state'];

    protected array $fields = ['code', 'name', 'detail'];

    protected bool $audited = true;

    public function find(int $id): Category
    {
        return Category::query()->findOrFail($id);
    }

    protected function query(): Builder
    {
        return parent::query()->withCount('products');
    }

    public function paginateRows(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->paginate($filters, $perPage)->through(fn (Category $category) => [
            'id' => (int) $category->codmov,
            'code' => $category->code,
            'name' => $category->name,
            'detail' => $category->detail,
            'state' => (int) $category->state,
            'products_count' => (int) $category->products_count,
        ]);
    }

    public function destroy(Category $category): array
    {
        if ($category->products()->exists()) {
            return ['data' => $category, 'flash' => $this->toast('No puedes eliminar una categoría con productos. Muévelos a otra o inactívala.', 'error')];
        }

        $category->delete();

        return ['data' => $category, 'flash' => $this->toast('Categoría eliminada.')];
    }
}
