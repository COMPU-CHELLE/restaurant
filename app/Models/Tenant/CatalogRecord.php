<?php

namespace App\Models\Tenant;

use App\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/** Fila de CUALQUIER tabla de catálogo de la empresa. Ver Support\CatalogRecord. */
class CatalogRecord extends Model
{
    use UsesTenantConnection;

    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    protected function casts(): array
    {
        return ['imported_at' => 'datetime'];
    }

    public static function forTable(string $table): static
    {
        return (new static())->setTable($table);
    }

    /** Total de parámetros de la empresa (suma de todas las tablas de catálogo). */
    public static function totalRows(): int
    {
        $total = 0;

        foreach (array_keys(config('catalogs.types')) as $type) {
            try {
                $total += static::forTable($type)->newQuery()->count();
            } catch (Throwable) {
                // tabla sin migrar en esta empresa
            }
        }

        return $total;
    }
}
