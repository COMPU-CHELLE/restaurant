<?php

namespace Database\Seeders;

use App\Models\Support\Level;
use App\Models\Support\Module;
use App\Models\Support\Country;
use App\Models\Support\Zone;
use App\Models\Support\Subzone;
use App\Models\Support\Currency;
use App\Models\Support\Permission;
use App\Models\Support\Role;
use Illuminate\Database\Seeder;
use App\Models\Support\Soflog;
use Illuminate\Support\Str;

/**
 * Roles + árbol Módulo → Nivel + permisos (slug-de-la-url.accion) de la base support.
 * Es idempotente y también ordena: re-apunta niveles que cambiaron de módulo/URL y
 * elimina los permisos que ya no existen en el árbol.
 *
 *   php artisan db:seed --class=SupportSecuritySeeder
 *
 * Los niveles pueden traer "items" (subitems anidados en el menú; p. ej. cada catálogo bajo Parámetros).
 *
 * Ámbitos (se deducen de la URL del módulo):
 *   /soporte  → gestión global (solo SOPORTE)         → sin permisos por perfil
 *   /admin    → administración y configuración de la empresa (ADMIN) → sin permisos por perfil
 *   resto     → operativo, se asigna por perfil       → con permisos
 */
class SupportSecuritySeeder extends Seeder
{
    private const BASE = ['view', 'store', 'update', 'toggle', 'delete', 'restore'];

    private const ACTION_LABELS = [
        'view' => 'Ver', 'store' => 'Crear', 'update' => 'Editar', 'toggle' => 'Activar e inactivar',
        'delete' => 'Eliminar', 'restore' => 'Restaurar', 'force' => 'Eliminar físico', 'print' => 'Imprimir',
        'layout' => 'Diseñar plano', 'pay' => 'Cobrar', 'move' => 'Mover mesa', 'split' => 'Dividir cuenta',
        'open' => 'Abrir caja', 'close' => 'Cerrar caja', 'movement' => 'Registrar ingresos/egresos',
        'prices' => 'Gestionar precios', 'images' => 'Gestionar imágenes',
    ];

    /** @var array<int, int> ids de permisos vigentes */
    private array $keep = [];

    public function run(): void
    {
        $audit = [
            'host'   => 'SYSTEM',
            'codope' => 'SOPORTE',
            'basdat' => 'chellnova',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        foreach ([
            ['SOPORTE', 'Soporte', 'Plataforma central'],
            ['ADMIN', 'Administrador', 'Administrador del restaurante'],
            ['USER', 'Usuario', 'Usuario operativo (permisos por perfil)'],
        ] as [$code, $name, $detail]) {
            $role = Role::updateOrCreate(['code' => $code], ['name' => $name, 'detail' => $detail, 'state' => 1]);

            Soflog::firstOrCreate(
                [
                    'tipeve' => 'CREATE',
                    'codmod' => 'SYSTEM',
                    'detail' => 'Se registró el rol ' . $role->name,
                ],
                [
                    'period'     => date('Y-m'),
                    'anopro'     => date('Y'),
                    'mespro'     => date('m'),
                    'diapro'     => date('d'),
                    'fecope'     => now(),
                    'old_values' => null,
                    'new_values' => $role->toArray(),
                    'host'       => $audit['host'],
                    'codope'     => $audit['codope'],
                    'basdat'     => $audit['basdat'],
                ]
            );

            $this->command->info("✅ Rol {$role->name} creado correctamente.");
        }

        $tree = $this->tree();
        $this->removeStale($tree);

        $counter = (int) Permission::withTrashed()->max('code');

        foreach ($tree as $modCode => $mod) {
            $module = Module::updateOrCreate(['code' => $modCode], [
                'name' => $mod['name'], 'detail' => $mod['name'], 'url' => $mod['url'], 'icon' => $mod['icon'], 'state' => 1,
            ]);

            Soflog::firstOrCreate(
                [
                    'tipeve' => 'CREATE',
                    'codmod' => 'SYSTEM',
                    'detail' => 'Se registró el modulo ' . $module->name,
                ],
                [
                    'period'     => date('Y-m'),
                    'anopro'     => date('Y'),
                    'mespro'     => date('m'),
                    'diapro'     => date('d'),
                    'fecope'     => now(),
                    'old_values' => null,
                    'new_values' => $module->toArray(),
                    'host'       => $audit['host'],
                    'codope'     => $audit['codope'],
                    'basdat'     => $audit['basdat'],
                ]
            );

            $this->command->info("✅ Modulo {$module->name} creado correctamente.");

            // Solo los módulos operativos llevan permiso "view" propio (aparecen en el menú si el perfil lo tiene).
            if ($mod['perms']) {
                $this->permission($counter, $module->codmov, null, $this->slug($mod['url']) . '.view', 'Ver ' . Str::lower($mod['name']));
            }

            foreach ($mod['levels'] as $levCode => $lev) {
                $level = Level::updateOrCreate(['code' => $levCode], [
                    'name' => $lev['name'], 'detail' => $lev['name'], 'icon' => $lev['icon'],
                    'url' => $lev['url'], 'parent' => $modCode, 'type' => 'LEVEL', 'state' => 1,
                ]);

                $this->command->info("✅ Submodulo {$level->name} creado correctamente.");

                Soflog::firstOrCreate(
                    [
                        'tipeve' => 'CREATE',
                        'codmod' => 'SYSTEM',
                        'detail' => 'Se registró el modulo ' . $level->name,
                    ],
                    [
                        'period'     => date('Y-m'),
                        'anopro'     => date('Y'),
                        'mespro'     => date('m'),
                        'diapro'     => date('d'),
                        'fecope'     => now(),
                        'old_values' => null,
                        'new_values' => $level->toArray(),
                        'host'       => $audit['host'],
                        'codope'     => $audit['codope'],
                        'basdat'     => $audit['basdat'],
                    ]
                );

                // Subitems (ITEM) del nivel: se muestran anidados en el menú.
                foreach ($lev['items'] ?? [] as $itemCode => $item) {
                    $item = Level::updateOrCreate(['code' => $itemCode], [
                        'name' => $item['name'], 'detail' => $item['name'], 'icon' => $item['icon'],
                        'url' => $item['url'], 'parent' => $levCode, 'type' => 'ITEM', 'state' => 1,
                    ]);

                    $this->command->info("✅ Item {$item->name} creado correctamente.");

                    Soflog::firstOrCreate(
                        [
                            'tipeve' => 'CREATE',
                            'codmod' => 'SYSTEM',
                            'detail' => 'Se registró el modulo ' . $item->name,
                        ],
                        [
                            'period'     => date('Y-m'),
                            'anopro'     => date('Y'),
                            'mespro'     => date('m'),
                            'diapro'     => date('d'),
                            'fecope'     => now(),
                            'old_values' => null,
                            'new_values' => $item->toArray(),
                            'host'       => $audit['host'],
                            'codope'     => $audit['codope'],
                            'basdat'     => $audit['basdat'],
                        ]
                    );
                }

                if (! $mod['perms']) {
                    continue;
                }

                foreach ($lev['actions'] as $action) {
                    $this->permission(
                        $counter, $module->codmov, $level->codmov,
                        $this->slug($lev['url']) . '.' . $action,
                        (self::ACTION_LABELS[$action] ?? $action) . ' ' . Str::lower($lev['name'])
                    );
                }
            }
        }

        // Permisos que ya no pertenecen al árbol (p. ej. los de módulos que pasaron a /admin).
        Permission::query()->whereNotIn('codmov', $this->keep)->delete();

        $path = storage_path('app/data/countries/countries.json');
        if (!file_exists($path)) {
            throw new \Exception('countries.json no existe');
        }

        $countries = json_decode(file_get_contents($path), true);
        $insertedCountries = 0;
        foreach ($countries as $country) {
            $country = Country::firstOrCreate(
                ['iso2' => $country['iso2']],
                [
                    'iso3'        => $country['iso3'] ?? null,
                    'iso_numeric' => $country['iso_numeric'] ?? null,
                    'factus_id'   => $country['factus_id'] ?? null,
                    'name'        => $country['name'],
                    'region'      => $country['region'] ?? null,
                    'subregion'   => $country['subregion'] ?? null,
                    'state'       => 1,
                    'host'       => $audit['host'],
                    'codope'     => $audit['codope'],
                    'basdat'     => $audit['basdat'],
                ]
            );

            $insertedCountries++;

            Soflog::firstOrCreate(
                        [
                    'tipeve' => 'CREATE',
                    'codmod' => 'SYSTEM',
                    'detail' => 'Se registró el pais ' . $country->name,
                ],
                [
                    'period'     => date('Y-m'),
                    'anopro'     => date('Y'),
                    'mespro'     => date('m'),
                    'diapro'     => date('d'),
                    'fecope'     => now(),
                    'old_values' => null,
                    'new_values' => $country->toArray(),
                    'host'       => $audit['host'],
                    'codope'     => $audit['codope'],
                    'basdat'     => $audit['basdat'],
                ]
            );

        }

        $this->command->info("✅ Países creados correctamente. Total: {$insertedCountries}");

        $path = storage_path('app/data/zones/zones.json');
        if (!file_exists($path)) {
            throw new \Exception('zones.json no existe');
        }

        $zones = json_decode(file_get_contents($path), true);
        // Filtrar zonas solo para países existentes
        $existingCountryIso2 = Country::pluck('iso2')->toArray();
        $insertedZones = 0;
        foreach ($zones as $zone) {
            if (empty($zone['codcoi']) || !in_array($zone['codcoi'], $existingCountryIso2)) {
                continue; // Saltar zonas cuyo país no existe
            }
            $country = Country::where('iso2', $zone['codcoi'])->first();
            $zone = Zone::firstOrCreate(
                ['code' => $zone['code']],
                [
                    'name'   => $zone['name'],
                    'codcoi' => $country['codmov'] ?? null,
                    'state'  => 1,
                    'host'       => $audit['host'],
                    'codope'     => $audit['codope'],
                    'basdat'     => $audit['basdat'],
                ]
            );

            $insertedZones++;

            Soflog::firstOrCreate(
                        [
                    'tipeve' => 'CREATE',
                    'codmod' => 'SYSTEM',
                    'detail' => 'Se registró la zona ' . $zone->name,
                ],
                [
                    'period'     => date('Y-m'),
                    'anopro'     => date('Y'),
                    'mespro'     => date('m'),
                    'diapro'     => date('d'),
                    'fecope'     => now(),
                    'old_values' => null,
                    'new_values' => $zone->toArray(),
                    'host'       => $audit['host'],
                    'codope'     => $audit['codope'],
                    'basdat'     => $audit['basdat'],
                ]
            );
        }
        $this->command->info("✅ Zonas creadas correctamente. Total: {$insertedZones}");

        $path = storage_path('app/data/subzones/subzones.json');
        if (!file_exists($path)) {
            throw new \Exception('subzones.json no existe');
        }
        $subzones = json_decode(file_get_contents($path), true);
        $insertedSubzones = 0;
        foreach ($subzones as $subzone) {
            $zone = Zone::where('code', $subzone['codzon'])->first();
            $subzone = Subzone::firstOrCreate(
                ['code' => $subzone['code']],
                [
                    'factus_id' => $subzone['factus_id'],
                    'iso_numeric' => $subzone['iso_numeric'] ?? null,
                    'name'   => $subzone['name'],
                    'codzon' => $zone['codmov'],
                    'state'  => 1,
                    'host'       => $audit['host'],
                    'codope'     => $audit['codope'],
                    'basdat'     => $audit['basdat'],
                ]
            );

            $insertedSubzones++;

            Soflog::firstOrCreate(
                        [
                    'tipeve' => 'CREATE',
                    'codmod' => 'SYSTEM',
                    'detail' => 'Se registró la subzona ' . $subzone->name,
                ],
                [
                    'period'     => date('Y-m'),
                    'anopro'     => date('Y'),
                    'mespro'     => date('m'),
                    'diapro'     => date('d'),
                    'fecope'     => now(),
                    'old_values' => null,
                    'new_values' => $subzone->toArray(),
                    'host'       => $audit['host'],
                    'codope'     => $audit['codope'],
                    'basdat'     => $audit['basdat'],
                ]
            );
        }

        $this->command->info("✅ Subzonas creadas correctamente. Total: {$insertedSubzones}");

        $path = storage_path('app/data/currencies/currencies.json');
        if (!file_exists($path)) {
            throw new \Exception('currencies.json no existe');
        }
        $currencies = json_decode(file_get_contents($path), true);
        $insertedCurrencies = 0;
        foreach ($currencies as $currency) {
            if (empty($currency['code']) || empty($currency['name'])) {
                continue;
            }
            $country = Country::where('iso2', $currency['codcoi'])->first();
            $currency = Currency::firstOrCreate(
                ['code' => $currency['code']], // ISO 4217 (USD, COP, EUR)
                [
                    'name'   => $currency['name'],
                    'symbol' => $currency['symbol'] ?? null,
                    'detail' => $currency['detail'] ?? null,
                    'codcoi' => $country['codmov'] ?? null, // ISO2 del país

                    'state'  => 1,
                    'host'       => $audit['host'],
                    'codope'     => $audit['codope'],
                    'basdat'     => $audit['basdat'],
                ]
            );

            $insertedCurrencies++;

            Soflog::firstOrCreate(
                        [
                    'tipeve' => 'CREATE',
                    'codmod' => 'SYSTEM',
                    'detail' => 'Se registró la moneda ' . $currency->name,
                ],
                [
                    'period'     => date('Y-m'),
                    'anopro'     => date('Y'),
                    'mespro'     => date('m'),
                    'diapro'     => date('d'),
                    'fecope'     => now(),
                    'old_values' => null,
                    'new_values' => $currency->toArray(),
                    'host'       => $audit['host'],
                    'codope'     => $audit['codope'],
                    'basdat'     => $audit['basdat'],
                ]
            );
        }

        $this->command->info("✅ Monedas creadas correctamente. Total: {$insertedCurrencies}");
    }

    /**
     * Elimina niveles, subitems y módulos que ya no están en el árbol (y sus permisos), para que no
     * sigan apareciendo en el menú ni ocupen una URL. Se hace antes de crear para liberar las URL.
     */
    private function removeStale(array $tree): void
    {
        $moduleCodes = array_keys($tree);
        $levelCodes = [];

        foreach ($tree as $mod) {
            foreach ($mod['levels'] as $levCode => $lev) {
                $levelCodes[] = $levCode;
                array_push($levelCodes, ...array_keys($lev['items'] ?? []));
            }
        }

        $staleLevels = Level::withTrashed()->whereNotIn('code', $levelCodes)->pluck('codmov');

        if ($staleLevels->isNotEmpty()) {
            Permission::withTrashed()
                ->where(fn ($q) => $q->whereIn('codniv', $staleLevels)->orWhereIn('codite', $staleLevels))
                ->forceDelete();
            Level::withTrashed()->whereIn('codmov', $staleLevels)->forceDelete();
        }

        $staleModules = Module::withTrashed()->whereNotIn('code', $moduleCodes)->pluck('codmov');

        if ($staleModules->isNotEmpty()) {
            Permission::withTrashed()->whereIn('codmod', $staleModules)->forceDelete();
            Module::withTrashed()->whereIn('codmov', $staleModules)->forceDelete();
        }
    }

    private function permission(int &$counter, int $codmod, ?int $codniv, string $name, string $detail): void
    {
        $existing = Permission::withTrashed()
            ->where('codmod', $codmod)
            ->where('codniv', $codniv)
            ->whereNull('codite')
            ->where('name', $name)
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }
            $this->keep[] = (int) $existing->codmov;

            return;
        }

        $permission = Permission::create([
            'code'   => Str::padLeft((string) ++$counter, 3, '0'),
            'name'   => $name,
            'detail' => $detail,
            'codmod' => $codmod,
            'codniv' => $codniv,
            'codite' => null,
        ]);

        $this->keep[] = (int) $permission->codmov;
    }

    private function slug(string $url): string
    {
        return trim(str_replace('/', '.', trim($url, '/')), '.');
    }

    /** Subitems con código estable por posición: ITM + número del nivel + orden (ordena el menú). */
    private function items(string $levelCode, array $rows): array
    {
        $items = [];

        foreach (array_values($rows) as $i => [$name, $url, $icon]) {
            $items['ITM' . substr($levelCode, 3) . sprintf('%02d', $i + 1)] = ['name' => $name, 'url' => $url, 'icon' => $icon];
        }

        return $items;
    }

    /** Catálogos de config/catalogs.php como filas de menú: [nombre, url, ícono]. */
    private function catalogRows(string $base): array
    {
        $rows = [
            'currencies' => ['Monedas', 'Coins'],
            'document_classes' => ['Clases de documento', 'FileText'],
            'tax_types' => ['Tipos de impuesto', 'Receipt'],
            'tax_rates' => ['Impuestos', 'Percent'],
            'units' => ['Unidades de medida', 'Ruler'],
            'payment_forms' => ['Formas de pago', 'CreditCard'],
            'payment_means' => ['Medios de pago', 'Wallet'],
            'account_types' => ['Tipos de cuenta', 'BookOpen'],
            'retention_types' => ['Tipos de retención', 'Percent'],
            'movement_types' => ['Tipos de movimiento', 'ArrowLeftRight'],
            'economic_activities' => ['Actividades económicas', 'Briefcase'],
            'pdu_types' => ['Tipos de PDU', 'Layers'],
            'accounts' => ['Cuentas', 'Hash'],
            'fiscal_responsibilities' => ['Responsabilidades fiscales', 'ScrollText'],
        ];

        return collect($rows)->map(fn ($row, $key) => [$row[0], "{$base}/{$key}", $row[1]])->values()->all();
    }

    private function tree(): array
    {
        $crud = self::BASE;

        return [
            // ---------------- OPERATIVO (por perfil) ----------------
            'MOD001' => ['name' => 'Operación', 'url' => '/operacion', 'icon' => 'LayoutGrid', 'perms' => true, 'levels' => [
                'NIV001' => ['name' => 'Mesas', 'url' => '/operacion/mesas', 'icon' => 'Armchair', 'actions' => ['view', 'store', 'update', 'delete', 'layout']],
                'NIV002' => ['name' => 'Órdenes', 'url' => '/operacion/ordenes', 'icon' => 'ClipboardList', 'actions' => ['view', 'store', 'update', 'delete', 'pay', 'move', 'split', 'print']],
                'NIV003' => ['name' => 'Caja', 'url' => '/operacion/caja', 'icon' => 'Banknote', 'actions' => ['view', 'open', 'close', 'movement', 'print']],
            ]],
            'MOD002' => ['name' => 'Catálogo', 'url' => '/catalogo', 'icon' => 'Package', 'perms' => true, 'levels' => [
                'NIV004' => ['name' => 'Productos', 'url' => '/catalogo/productos', 'icon' => 'Package', 'actions' => array_merge($crud, ['prices', 'images'])],
                'NIV005' => ['name' => 'Categorías', 'url' => '/catalogo/categorias', 'icon' => 'Tags', 'actions' => $crud],
            ]],
            'MOD003' => ['name' => 'Inventario', 'url' => '/inventario', 'icon' => 'Boxes', 'perms' => true, 'levels' => [
                'NIV006' => ['name' => 'Movimientos de inventario', 'url' => '/inventario/movimientos', 'icon' => 'Boxes', 'actions' => ['view', 'store', 'print']],
            ]],
            'MOD004' => ['name' => 'Terceros', 'url' => '/terceros', 'icon' => 'Truck', 'perms' => true, 'levels' => [
                'NIV007' => ['name' => 'Clientes y proveedores', 'url' => '/terceros/contactos', 'icon' => 'Truck', 'actions' => $crud],
            ]],
            'MOD007' => ['name' => 'Reportes', 'url' => '/reportes', 'icon' => 'BarChart3', 'perms' => true, 'levels' => [
                'NIV015' => ['name' => 'Ventas', 'url' => '/reportes/ventas', 'icon' => 'BarChart3', 'actions' => ['view', 'print']],
            ]],

            // ---------------- ADMINISTRADOR (de la empresa) ----------------
            'MOD005' => ['name' => 'Administración', 'url' => '/admin', 'icon' => 'ShieldCheck', 'perms' => false, 'levels' => [
                'NIV008' => ['name' => 'Empresa', 'url' => '/admin/empresa', 'icon' => 'Building2', 'actions' => []],
                'NIV013' => ['name' => 'Usuarios', 'url' => '/admin/usuarios', 'icon' => 'Users', 'actions' => []],
                'NIV016' => ['name' => 'Parámetros', 'url' => '/admin/parametros', 'icon' => 'SlidersHorizontal', 'actions' => [],
                    'items' => $this->items('NIV016', array_merge([
                        ['Sedes', '/admin/parametros/branches', 'Building'],
                        ['Perfiles', '/admin/perfiles', 'IdCard'],
                        ['Asignar permisos', '/admin/asignar', 'KeySquare'],
                    ], $this->catalogRows('/admin/parametros'), [
                        // Parámetros propios del restaurante (solo de cada empresa; no vienen de soporte).
                        ['Estados de pedido', '/admin/parametros/order_statuses', 'ClipboardList'],
                        ['Motivos de anulación', '/admin/parametros/cancellation_reasons', 'SlidersHorizontal'],
                        ['Motivos de inventario', '/admin/parametros/inventory_reasons', 'Boxes'],
                        ['Áreas de preparación', '/admin/parametros/preparation_areas', 'Printer'],
                    ]))],
                'NIV017' => ['name' => 'Facturas', 'url' => '/admin/facturas', 'icon' => 'Receipt', 'actions' => []],
                'NIV029' => ['name' => 'Recaudos', 'url' => '/admin/recaudos', 'icon' => 'QrCode', 'actions' => []],
                'NIV025' => ['name' => 'Tickets', 'url' => '/admin/tickets', 'icon' => 'Ticket', 'actions' => []],
                'NIV026' => ['name' => 'Impresiones', 'url' => '/admin/impresiones', 'icon' => 'Printer', 'actions' => [],
                    'items' => $this->items('NIV026', [
                        ['Impresoras', '/admin/impresiones/impresoras', 'Printer'],
                        ['Comandas', '/admin/impresiones/comandas', 'ClipboardList'],
                    ])],
                'NIV027' => ['name' => 'Bitácora', 'url' => '/admin/bitacora', 'icon' => 'ScrollText', 'actions' => []],
            ]],
            'MOD006' => ['name' => 'Configuración', 'url' => '/admin/configuracion', 'icon' => 'Settings', 'perms' => false, 'levels' => [
                'NIV009' => ['name' => 'Salones y áreas', 'url' => '/admin/configuracion/areas', 'icon' => 'MapPin', 'actions' => []],
                'NIV010' => ['name' => 'Cajas registradoras', 'url' => '/admin/configuracion/cajas', 'icon' => 'Wallet', 'actions' => []],
                'NIV011' => ['name' => 'Métodos de pago', 'url' => '/admin/configuracion/metodos-pago', 'icon' => 'CreditCard', 'actions' => []],
            ]],

            // ---------------- SOPORTE (global) ----------------
            'MOD008' => ['name' => 'Soporte', 'url' => '/soporte', 'icon' => 'LifeBuoy', 'perms' => false, 'levels' => [
                'NIV018' => ['name' => 'Empresas', 'url' => '/soporte/empresas', 'icon' => 'Building2', 'actions' => []],
                'NIV019' => ['name' => 'Usuarios', 'url' => '/soporte/usuarios', 'icon' => 'Users', 'actions' => []],
                'NIV020' => ['name' => 'Parámetros', 'url' => '/soporte/parametros', 'icon' => 'SlidersHorizontal', 'actions' => [],
                    'items' => $this->items('NIV020', array_merge([
                        ['Módulos', '/soporte/parametros/modules', 'Boxes'],
                        ['Submódulos', '/soporte/parametros/levels', 'Layers'],
                        ['Roles', '/soporte/parametros/roles', 'UserCog'],
                        ['Permisos', '/soporte/parametros/permisos', 'KeyRound'],
                        ['Países', '/soporte/paises', 'Globe2'],
                        ['Zonas', '/soporte/zonas', 'Map'],
                        ['Subzonas', '/soporte/subzonas', 'MapPin'],
                    ], $this->catalogRows('/soporte/parametros')))],
                'NIV021' => ['name' => 'Facturas', 'url' => '/soporte/facturas', 'icon' => 'Receipt', 'actions' => []],
                'NIV022' => ['name' => 'Factus', 'url' => '/soporte/factus', 'icon' => 'PlugZap', 'actions' => [],
                    'items' => $this->items('NIV022', [
                        ['Configuración', '/soporte/factus/configuracion', 'Workflow'],
                        ['Historial de emitidos y errores', '/soporte/factus/historial', 'History'],
                        ['Consumo vs plan adquirido', '/soporte/factus/consumo', 'Gauge'],
                    ])],
                'NIV028' => ['name' => 'Factus Pay', 'url' => '/soporte/factus-pay', 'icon' => 'QrCode', 'actions' => []],
                'NIV023' => ['name' => 'Tickets', 'url' => '/soporte/tickets', 'icon' => 'Ticket', 'actions' => []],
                'NIV024' => ['name' => 'Bitácora global', 'url' => '/soporte/bitacora', 'icon' => 'ScrollText', 'actions' => []],
            ]],
        ];
    }
}
