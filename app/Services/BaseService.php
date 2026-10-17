<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Servicio base (misma idea que el de Chellnova): búsqueda, orden, paginación y
 * respuestas  ['data' => ..., 'flash' => ['toast' => mensaje, 'type' => success|info|warning|error]].
 * Cada servicio declara su modelo, qué columnas se buscan/ordenan y qué campos se guardan.
 */
abstract class BaseService
{
    /** @var class-string<Model> */
    protected string $model;

    /** Nombre para los mensajes: "Empresa creada correctamente." */
    protected string $label = 'Registro';

    /** Columnas donde busca el texto de la barra de búsqueda. */
    protected array $searchable = [];

    /** Columnas por las que se puede ordenar (la primera es la predeterminada). */
    protected array $sortable = ['codmov'];

    /** Campos que se pueden guardar desde un formulario. */
    protected array $fields = [];

    /** Si la tabla lleva las columnas de auditoría host / codope. */
    protected bool $audited = false;

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query();

        $this->applySearch($query, trim((string) ($filters['search'] ?? '')));
        $this->applyFilters($query, $filters);

        $sort = in_array($filters['sort'] ?? null, $this->sortable, true) ? $filters['sort'] : $this->sortable[0];
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)->paginate($perPage)->withQueryString();
    }

    public function store(array $payload): array
    {
        return DB::transaction(function () use ($payload) {
            $record = new $this->model();
            $record->forceFill($this->stamp($this->onlyFields($payload), true))->save();

            return ['data' => $record, 'flash' => $this->toast("{$this->label} creado correctamente.")];
        });
    }

    public function update(Model $record, array $payload): array
    {
        return DB::transaction(function () use ($record, $payload) {
            $record->forceFill($this->stamp($this->onlyFields($payload), false))->save();

            return ['data' => $record, 'flash' => $this->toast("{$this->label} actualizado correctamente.")];
        });
    }

    public function toggleState(Model $record): array
    {
        $record->forceFill(['state' => (int) $record->state === 1 ? 0 : 1])->save();

        $text = (int) $record->state === 1 ? 'activado' : 'inactivado';

        return ['data' => $record, 'flash' => $this->toast("{$this->label} {$text}.")];
    }

    protected function query(): Builder
    {
        return ($this->model)::query();
    }

    protected function applySearch(Builder $query, string $search): void
    {
        if ($search === '' || ! $this->searchable) {
            return;
        }

        $like = '%' . addcslashes($search, '%_\\') . '%';

        $query->where(function (Builder $q) use ($like) {
            foreach ($this->searchable as $column) {
                $q->orWhere($column, 'like', $like);
            }
        });
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['state']) && in_array((string) $filters['state'], ['0', '1'], true)) {
            $query->where('state', (int) $filters['state']);
        }
    }

    protected function onlyFields(array $payload): array
    {
        return array_intersect_key($payload, array_flip($this->fields));
    }

    protected function stamp(array $data, bool $creating): array
    {
        if ($this->audited) {
            $data['host'] = request()->ip();
            $data['codope'] = auth()->user()?->code;
        }

        return $data;
    }

    protected function toast(string $message, string $type = 'success'): array
    {
        return ['toast' => $message, 'type' => $type];
    }
}
