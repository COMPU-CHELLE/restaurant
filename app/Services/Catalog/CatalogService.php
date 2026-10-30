<?php

namespace App\Services\Catalog;

use App\Services\BaseService;
use App\Support\CatalogDefinition;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Motor común de catálogos. Cada catálogo vive en su propia tabla (nombre = clave de config/catalogs.php)
 * en la base support (soporte) o en la base de la empresa.
 */
abstract class CatalogService extends BaseService
{
    protected string $label = 'Parámetro';

    protected array $searchable = ['code', 'name', 'detail'];

    protected array $sortable = ['codmov', 'code', 'name', 'state'];

    protected bool $audited = true;

    protected string $type = '';

    /** 'support' (soporte) o 'tenant' (empresa): decide qué catálogos ve cada pantalla. */
    abstract protected function scopeName(): string;

    /** Modelo genérico apuntando a la tabla del catálogo. */
    protected function record(string $type): Model
    {
        return ($this->model)::forTable($type);
    }

    protected function visible(array $definition): bool
    {
        $scope = $definition['scope'] ?? 'both';

        return $scope === 'both'
            || ($scope === 'tenant' && $this->scopeName() === 'tenant')
            || ($scope === 'support' && $this->scopeName() === 'support');
    }

    /** Definición del catálogo con sus columnas completas (incluye code_fe y factus_id cuando aplica). */
    public function definition(string $type): array
    {
        $definition = CatalogDefinition::find($type);

        abort_if($definition === null || ! $this->visible($definition), 404);

        return array_merge(['key' => $type], $definition, ['fields' => CatalogDefinition::fields($definition)]);
    }

    /** Lista de catálogos con la cantidad de elementos de cada uno. */
    public function types(): array
    {
        return collect(config('catalogs.types'))
            ->filter(fn ($definition) => $this->visible($definition))
            ->map(fn ($definition, $key) => ['key' => $key, 'label' => $definition['label'], 'count' => $this->count($key)])
            ->values()
            ->all();
    }

    protected function count(string $type): int
    {
        try {
            $query = $this->record($type)->newQuery();
            $connection = $this->scopeName() === 'support' ? 'mysql' : 'tenant';
            if (Schema::connection($connection)->hasColumn($type, 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            return $query->count();
        } catch (Throwable) {
            return 0; // tabla todavía sin migrar
        }
    }

    public function paginateType(string $type, array $filters, int $perPage): LengthAwarePaginator
    {
        $this->definition($type);
        $this->type = $type;

        return $this->paginate($filters, $perPage)->through(fn (Model $item) => $this->toRow($item));
    }

    public function toRow(Model $item): array
    {
        $extra = [];

        foreach ($this->definition($this->type)['fields'] as $field) {
            $value = $item->getAttribute($field['key']);

            if ($value === null || $value === '') {
                continue;
            }

            $extra[$field['key']] = $field['type'] === 'number' ? $this->number($value) : (string) $value;
        }

        return [
            'id' => (int) $item->codmov,
            'code' => $item->code,
            'name' => $item->name,
            'detail' => $item->detail,
            'extra' => $extra,
            'state' => (int) $item->state,
            'source_id' => $item->getAttribute('source_id'),
        ];
    }

    /** Opciones de los campos que apuntan a otro catálogo: ['tax_types' => [['code', 'name'], …]]. */
    public function references(string $type): array
    {
        $references = [];

        foreach ($this->definition($type)['fields'] as $field) {
            if (($field['type'] ?? null) === 'catalog') {
                $references[$field['source']] = $this->record($field['source'])->newQuery()
                    ->where('state', 1)->orderBy('code')->get(['code', 'name'])
                    ->map(fn ($item) => ['code' => $item->code, 'name' => $item->name])->all();
            }
        }

        return $references;
    }

    public function createItem(string $type, array $payload): array
    {
        $definition = $this->definition($type);

        return $this->transaction(function () use ($type, $definition, $payload) {
            $item = $this->record($type);
            $item->forceFill($this->stamp([
                'code' => $payload['code'],
                'name' => $payload['name'],
                'detail' => $payload['detail'] ?? null,
                'state' => 1,
            ] + $this->columns($definition, $payload['extra'] ?? []), true))->save();

            return ['data' => $item, 'flash' => $this->toast("{$definition['label']}: registro creado correctamente.")];
        });
    }

    public function updateItem(string $type, int $id, array $payload): array
    {
        $definition = $this->definition($type);
        $item = $this->findItem($type, $id);

        return $this->transaction(function () use ($item, $definition, $payload) {
            $item->forceFill($this->stamp([
                'code' => $payload['code'],
                'name' => $payload['name'],
                'detail' => $payload['detail'] ?? null,
            ] + $this->columns($definition, $payload['extra'] ?? []), false))->save();

            return ['data' => $item, 'flash' => $this->toast("{$definition['label']}: registro actualizado correctamente.")];
        });
    }

    public function toggleItem(string $type, int $id): array
    {
        $this->definition($type);

        return $this->toggleState($this->findItem($type, $id));
    }

    public function destroyItem(string $type, int $id): array
    {
        $this->definition($type);
        $item = $this->findItem($type, $id);
        $connection = $this->scopeName() === 'support' ? 'mysql' : 'tenant';

        if (Schema::connection($connection)->hasColumn($type, 'deleted_at')) {
            $item->forceFill(['deleted_at' => now(), 'state' => 0])->save();
        } else {
            $item->delete();
        }

        return ['data' => $item, 'flash' => $this->toast('Registro eliminado.')];
    }

    protected function findItem(string $type, int $id): Model
    {
        $query = $this->record($type)->newQuery();
        $connection = $this->scopeName() === 'support' ? 'mysql' : 'tenant';
        if (Schema::connection($connection)->hasColumn($type, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->findOrFail($id);
    }

    protected function query(): Builder
    {
        $query = $this->record($this->type)->newQuery();
        $connection = $this->scopeName() === 'support' ? 'mysql' : 'tenant';
        if (Schema::connection($connection)->hasColumn($this->type, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query;
    }

    /** Una columna por campo del catálogo: vacío = null (así se puede borrar un valor al editar). */
    protected function columns(array $definition, array $extra): array
    {
        $columns = [];

        foreach ($definition['fields'] as $field) {
            $value = $extra[$field['key']] ?? null;

            $columns[$field['key']] = ($value === null || $value === '') ? null
                : ($field['type'] === 'number' ? $this->number($value) : trim((string) $value));
        }

        return $columns;
    }

    protected function number(mixed $value): int|float
    {
        return floor((float) $value) == (float) $value ? (int) $value : (float) $value;
    }
}
