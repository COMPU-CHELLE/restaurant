<?php

namespace App\Services\Catalog;

use App\Models\Support\CatalogRecord as SupportRecord;
use App\Models\Tenant\CatalogRecord;
use App\Support\CatalogDefinition;
use Illuminate\Database\Eloquent\Model;

/** Parámetros de la empresa activa, con importación desde los de soporte. */
class TenantCatalogService extends CatalogService
{
    protected string $model = CatalogRecord::class;

    protected function scopeName(): string
    {
        return 'tenant';
    }

    public function toRow(Model $item): array
    {
        return parent::toRow($item) + ['imported' => $item->getAttribute('source_id') !== null];
    }

    /** Los catálogos exclusivos de la empresa no vienen de soporte. */
    private function importableType(string $type): bool
    {
        return (CatalogDefinition::find($type)['scope'] ?? 'both') === 'both';
    }

    /** Elementos activos de soporte que esta empresa todavía no tiene. */
    public function importable(string $type): int
    {
        $this->definition($type);

        if (! $this->importableType($type)) {
            return 0;
        }

        return SupportRecord::forTable($type)->newQuery()
            ->where('state', 1)
            ->whereNotIn('code', $this->record($type)->newQuery()->pluck('code'))
            ->count();
    }

    /** @return array{new:int, updated:int, skipped:int} */
    protected function importType(string $type, bool $update): array
    {
        $new = $updated = $skipped = 0;
        $columns = ['name', 'detail', ...CatalogDefinition::columns(CatalogDefinition::find($type))];
        $sources = SupportRecord::forTable($type)->newQuery()->where('state', 1)->get();

        $this->transaction(function () use ($type, $update, $sources, $columns, &$new, &$updated, &$skipped) {
            foreach ($sources as $source) {
                $values = [];
                foreach ($columns as $column) {
                    $values[$column] = $source->getAttribute($column);
                }

                $local = $this->record($type)->newQuery()->where('code', $source->code)->first();

                if (! $local) {
                    $this->record($type)->forceFill($this->stamp(
                        ['code' => $source->code, 'state' => 1, 'source_id' => $source->codmov, 'imported_at' => now()] + $values,
                        true,
                    ))->save();
                    $new++;
                } elseif ($update && (int) $local->getAttribute('source_id') === (int) $source->codmov) {
                    // Solo se actualizan los importados; los creados a mano con el mismo código no se tocan.
                    $local->forceFill($values)->save();
                    $updated++;
                } else {
                    $skipped++;
                }
            }
        });

        return compact('new', 'updated', 'skipped');
    }

    public function import(string $type, bool $update = false): array
    {
        $definition = $this->definition($type);
        $result = $this->importableType($type) ? $this->importType($type, $update) : ['new' => 0, 'updated' => 0, 'skipped' => 0];

        return ['data' => $result, 'flash' => $this->toast($this->summary($definition['label'], $result), $result['new'] + $result['updated'] > 0 ? 'success' : 'info')];
    }

    public function importAll(bool $update = false): array
    {
        $total = ['new' => 0, 'updated' => 0, 'skipped' => 0];

        foreach (array_keys(config('catalogs.types')) as $type) {
            if (! $this->importableType($type)) {
                continue;
            }

            foreach ($this->importType($type, $update) as $key => $value) {
                $total[$key] += $value;
            }
        }

        return ['data' => $total, 'flash' => $this->toast($this->summary('Todos los parámetros', $total), $total['new'] + $total['updated'] > 0 ? 'success' : 'info')];
    }

    protected function summary(string $label, array $result): string
    {
        return "{$label}: {$result['new']} nuevos, {$result['updated']} actualizados, {$result['skipped']} omitidos.";
    }
}
