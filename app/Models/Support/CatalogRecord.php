<?php

namespace App\Models\Support;

use App\Traits\UsesSupportConnection;
use Illuminate\Database\Eloquent\Model;

/**
 * Fila de CUALQUIER tabla de catálogo de soporte (units, document_classes, ...).
 * La tabla se elige con forTable(). Guardar siempre con forceFill(): el modelo es el mismo para
 * todas las tablas y la protección contra asignación masiva cachea las columnas de la primera.
 */
class CatalogRecord extends Model
{
    use UsesSupportConnection;

    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    public static function forTable(string $table): static
    {
        return (new static())->setTable($table);
    }
}
