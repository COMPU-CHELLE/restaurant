<?php

namespace App\Support;

/** Lee config/catalogs.php y agrega las columnas de Factus a los catálogos que las usan. */
class CatalogDefinition
{
    /** Columnas comunes de los catálogos que se envían a Factus. */
    public const FACTUS_FIELDS = [
        ['key' => 'code_fe', 'label' => 'Código factura electrónica (V2)', 'type' => 'text', 'max' => 20,
            'hint' => 'Lo que se envía a Factus V2.'],
        ['key' => 'factus_id', 'label' => 'ID / código Factus (V1)', 'type' => 'text', 'max' => 20,
            'hint' => 'Lo que se envía a Factus V1.'],
    ];

    public static function find(string $type): ?array
    {
        return config("catalogs.types.{$type}");
    }

    /** Solo los catálogos que definen soporte y las empresas importan se envían a Factus. */
    public static function hasFactus(array $definition): bool
    {
        return ($definition['factus'] ?? true) !== false && ($definition['scope'] ?? 'both') === 'both';
    }

    /** Campos propios + code_fe y factus_id cuando aplica. */
    public static function fields(array $definition): array
    {
        $fields = $definition['fields'] ?? [];

        return self::hasFactus($definition) ? array_merge($fields, self::FACTUS_FIELDS) : $fields;
    }

    /** Nombres de las columnas propias del catálogo (sin las comunes). */
    public static function columns(array $definition): array
    {
        return array_column(self::fields($definition), 'key');
    }
}
