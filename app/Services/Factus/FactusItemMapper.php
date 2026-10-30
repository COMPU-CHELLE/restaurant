<?php

namespace App\Services\Factus;

use App\Models\Tenant\Unit;

/**
 * Datos de ítems que comparten todos los builders de Factus.
 */
class FactusItemMapper
{
    /**
     * factus_id de las unidades usadas en el documento.
     * Se indexa por id local (units.codmov), que es lo que guardan los detalles,
     * y también por código para documentos antiguos que guardaron "UND", "KG", etc.
     */
    public static function unitFactusIds($details): array
    {
        $values = collect($details)
            ->pluck('undmed')
            ->filter()
            ->map(fn ($value) => (string) $value)
            ->unique()
            ->values();

        if ($values->isEmpty()) {
            return [];
        }

        $ids = $values->filter(fn ($value) => ctype_digit($value))->values();

        $units = Unit::query()
            ->where(function ($query) use ($values, $ids) {
                $query->whereIn('code', $values->all());
                if ($ids->isNotEmpty()) {
                    $query->orWhereIn('codmov', $ids->all());
                }
            })
            ->get(['codmov', 'code', 'factus_id']);

        $map = [];
        foreach ($units as $unit) {
            $map[(string) $unit->codmov] = $unit->factus_id;
            $map[(string) $unit->code] = $map[(string) $unit->code] ?? $unit->factus_id;
        }

        return $map;
    }

    public static function unitMeasureId(array $item, array $map, int $default = 70): int
    {
        return (int) ($map[(string) ($item['undmed'] ?? '')] ?? $default);
    }

    /**
     * Porcentaje de descuento de la línea. La tabla de detalles guarda el valor (valdes),
     * no el porcentaje, así que si no llega pordes se deriva de valdes.
     */
    public static function discountRate(array $item): float
    {
        if (isset($item['pordes']) && (float) $item['pordes'] > 0) {
            return (float) $item['pordes'];
        }

        $gross = (float) ($item['cantid'] ?? 0) * (float) ($item['valund'] ?? 0);
        $discount = (float) ($item['valdes'] ?? 0);

        return ($gross > 0 && $discount > 0) ? round(($discount / $gross) * 100, 2) : 0.0;
    }

    /**
     * Valor unitario CON IVA. En compras valund es el costo sin IVA (el IVA se suma encima),
     * y Factus recibe el valor unitario con impuesto incluido.
     */
    public static function priceWithTax(array $item): float
    {
        $rate = (float) ($item['poriva'] ?? 0);

        return round((float) ($item['valund'] ?? 0) * (1 + $rate / 100), 2);
    }
}
