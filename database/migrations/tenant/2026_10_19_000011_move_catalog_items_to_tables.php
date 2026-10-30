<?php

use App\Support\CatalogDefinition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Copia lo que había en la tabla única catalog_items a la tabla de cada catálogo y la elimina. */
return new class extends Migration
{
    private const CONN = 'tenant';

    public function up(): void
    {
        $db = Schema::connection(self::CONN);

        if (! $db->hasTable('catalog_items')) {
            return;
        }

        foreach (DB::connection(self::CONN)->table('catalog_items')->orderBy('codmov')->get() as $row) {
            $definition = config("catalogs.types.{$row->type}");

            if (! $definition || ! $db->hasTable($row->type)) {
                continue;
            }

            $extra = json_decode($row->extra ?? '[]', true) ?: [];
            $columns = CatalogDefinition::columns($definition);
            $factus = CatalogDefinition::hasFactus($definition);

            $data = [
                'code' => $row->code, 'name' => $row->name, 'detail' => $row->detail, 'state' => $row->state,
                'host' => $row->host, 'codope' => $row->codope, 'created_at' => $row->created_at, 'updated_at' => $row->updated_at,
            ];

            foreach ($columns as $column) {
                if (array_key_exists($column, $extra)) {
                    $data[$column] = $extra[$column];
                }
            }

            if ($factus) {
                $data['factus_id'] = $data['factus_id'] ?? ($extra['factus_id'] ?? $row->factus_id ?? null);
                $data['code_fe'] = $data['code_fe'] ?? ($extra['dian_code'] ?? null);
            }

            if (self::CONN === 'tenant' && $factus && ($row->source_id ?? null) !== null && $db->hasColumn($row->type, 'source_id')) {
                $data['source_id'] = DB::connection('mysql')->table($row->type)->where('code', $row->code)->value('codmov');
                $data['imported_at'] = $row->imported_at ?? null;
            }

            DB::connection(self::CONN)->table($row->type)->insertOrIgnore($data);
        }

        $db->dropIfExists('catalog_items');
    }

    public function down(): void
    {
        // Irreversible: los datos quedaron repartidos en las tablas de cada catálogo.
    }
};
