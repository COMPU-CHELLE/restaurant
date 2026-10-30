<?php

namespace Database\Seeders;

use App\Models\Support\Company;
use App\Models\Support\Module;
use App\Models\Support\Permission;
use App\Models\Support\Role;
use App\Models\Support\UserCompanyContext;
use App\Models\Support\UserRole;
use App\Models\Tenant\Profile;
use App\Models\Tenant\ProfilePermission;
use App\Models\User;
use App\Services\Tenant\TenantManager;
use Illuminate\Database\Seeder;
use App\Models\Support\Soflog;
use Illuminate\Support\Facades\DB;

class SupportDemoSeeder extends Seeder
{
    public function run(): void
    {
        $audit = [
            'host'   => 'SYSTEM',
            'codope' => 'SOPORTE',
            'basdat' => 'chellnova',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (app()->isProduction()) {
            $this->command?->error(
                'Este seeder solo se ejecuta en desarrollo.'
            );

            return;
        }

        $roles = Role::query()->pluck('codmov', 'code');

        foreach (['SOPORTE', 'ADMIN', 'USER'] as $code) {
            if (! $roles->has($code)) {
                $this->command?->error(
                    "No existe el rol {$code}. Ejecuta primero SupportSecuritySeeder."
                );

                return;
            }
        }

    /*
    |--------------------------------------------------------------------------
    | 1. Crear las empresas en la base central
    |--------------------------------------------------------------------------
    */

    $company = Company::updateOrCreate(
        ['code' => 'REST001'],
        [
            'empnom' => 'Restaurante Demo',
            'empnit' => '900000000',
            'empdig' => '1',
            'codcoi' => 'CO',
            'state' => 1,
        ]
    );

    Soflog::firstOrCreate(
        [
            'tipeve' => 'CREATE',
            'codmod' => 'SYSTEM',
            'detail' => 'Se registró la empresa ' . $company->name,
        ],
        [
            'period'     => date('Y-m'),
            'anopro'     => date('Y'),
            'mespro'     => date('m'),
            'diapro'     => date('d'),
            'fecope'     => now(),
            'old_values' => null,
            'new_values' => $company->toArray(),
            'host'       => $audit['host'],
            'codope'     => $audit['codope'],
            'basdat'     => $audit['basdat'],
        ]
    );

    $this->command->info("✅ Empresa $company->code creada corretatamente.");

    $company2 = Company::updateOrCreate(
        ['code' => 'REST002'],
        [
            'empnom' => 'Restaurante Demo 2',
            'empnit' => '900000001',
            'empdig' => '2',
            'codcoi' => 'CO',
            'state' => 1,
        ]
    );

    Soflog::firstOrCreate(
        [
            'tipeve' => 'CREATE',
            'codmod' => 'SYSTEM',
            'detail' => 'Se registró la empresa ' . $company2->name,
        ],
        [
            'period'     => date('Y-m'),
            'anopro'     => date('Y'),
            'mespro'     => date('m'),
            'diapro'     => date('d'),
            'fecope'     => now(),
            'old_values' => null,
            'new_values' => $company->toArray(),
            'host'       => $audit['host'],
            'codope'     => $audit['codope'],
            'basdat'     => $audit['basdat'],
        ]
    );

    $this->command->info("✅ Empresa $company2->code creada corretatamente.");
    /*
    |--------------------------------------------------------------------------
    | 2. Crear las bases de datos y ejecutar sus migraciones
    |--------------------------------------------------------------------------
    */

    $tenantManager = app(TenantManager::class);

    $company = $tenantManager->provision($company);
    $company2 = $tenantManager->provision($company2);

    /*
    |--------------------------------------------------------------------------
    | 3. Seleccionar la base del primer restaurante
    |--------------------------------------------------------------------------
    */

    $tenantManager->setTenantConnection($company->basdat);


        $gerente = Profile::updateOrCreate(
            ['code' => 'GERENTE'],
            [
                'name' => 'Gerente',
                'detail' => 'Acceso a todos los módulos operativos.',
                'state' => 1,
            ]
        );

        Soflog::firstOrCreate(
            [
                'tipeve' => 'CREATE',
                'codmod' => 'SYSTEM',
                'detail' => 'Se registró el prefil ' . $gerente->name,
            ],
            [
                'period'     => date('Y-m'),
                'anopro'     => date('Y'),
                'mespro'     => date('m'),
                'diapro'     => date('d'),
                'fecope'     => now(),
                'old_values' => null,
                'new_values' => $gerente->toArray(),
                'host'       => $audit['host'],
                'codope'     => $audit['codope'],
                'basdat'     => $audit['basdat'],
            ]
        );

        $this->command->info("✅ Perfil $gerente->code creado corretatamente.");

        $mesero = Profile::updateOrCreate(
            ['code' => 'MESERO'],
            [
                'name' => 'Mesero',
                'detail' => 'Acceso limitado a mesas y órdenes.',
                'state' => 1,
            ]
        );

        Soflog::firstOrCreate(
            [
                'tipeve' => 'CREATE',
                'codmod' => 'SYSTEM',
                'detail' => 'Se registró el prefil ' . $mesero->name,
            ],
            [
                'period'     => date('Y-m'),
                'anopro'     => date('Y'),
                'mespro'     => date('m'),
                'diapro'     => date('d'),
                'fecope'     => now(),
                'old_values' => null,
                'new_values' => $mesero->toArray(),
                'host'       => $audit['host'],
                'codope'     => $audit['codope'],
                'basdat'     => $audit['basdat'],
            ]
        );

        $this->command->info("✅ Perfil $mesero->code creado corretatamente.");

        /*
         * 3. Permisos del GERENTE.
         *
         * Incluye todos los permisos operativos.
         * Excluye Administración y Soporte.
         */
        $operationalModules = Module::query()
            ->whereIn('code', [
                'MOD001', // Operación
                'MOD002', // Catálogo
                'MOD003', // Inventario
                'MOD004', // Terceros
                'MOD005', // Configuración
                'MOD007', // Reportes
            ])
            ->pluck('codmov');

        $gerentePermissions = Permission::query()
            ->whereIn('codmod', $operationalModules)
            ->pluck('codmov')
            ->all();

        /*
         * 4. Permisos del MESERO.
         *
         * Solo puede acceder a las operaciones definidas aquí.
         */
        $meseroNames = [
            'operacion.view',
            'operacion.mesas.view',
            'operacion.mesas.update',
            'operacion.ordenes.view',
            'operacion.ordenes.store',
            'operacion.ordenes.update',
        ];

        $meseroPermissions = Permission::query()
            ->whereIn('name', $meseroNames)
            ->pluck('codmov')
            ->all();

        /*
         * Sincronizar los permisos de los dos perfiles de demostración.
         * No se modifican los permisos de otros perfiles.
         */
        DB::connection('tenant')
            ->table('profile_permissions')
            ->whereIn('profile_id', [
                $gerente->codmov,
                $mesero->codmov,
            ])
            ->delete();

        foreach (array_unique($gerentePermissions) as $permissionId) {
            ProfilePermission::firstOrCreate([
                'profile_id' => $gerente->codmov,
                'permission_id' => $permissionId,
            ]);
        }

        $this->command->info("✅ Permisos Asignados al perfil $gerente->code corretatamente.");

        foreach (array_unique($meseroPermissions) as $permissionId) {
            ProfilePermission::firstOrCreate([
                'profile_id' => $mesero->codmov,
                'permission_id' => $permissionId,
            ]);
        }

        $this->command->info("✅ Permisos Asignados al perfil $mesero->code corretatamente.");

        /*
         * 5. Crear los usuarios de demostración.
         *
         * No se sobrescriben las contraseñas de usuarios existentes.
         */
        $makeUser = function (
            string $code,
            string $name,
            string $email
        ): User {
            $user = User::firstOrNew(['email' => $email]);

            if (! $user->exists) {
                $user->code = $code;
                $user->name = $name;
                $user->password = 'password';
            }

            if (! $user->email_verified_at) {
                $user->email_verified_at = now();
            }

            $user->save();

            Soflog::firstOrCreate(
                [
                    'tipeve' => 'CREATE',
                    'codmod' => 'SYSTEM',
                    'detail' => 'Se registró el prefil ' . $user->name,
                ],
                [
                    'period'     => date('Y-m'),
                    'anopro'     => date('Y'),
                    'mespro'     => date('m'),
                    'diapro'     => date('d'),
                    'fecope'     => now(),
                    'old_values' => null,
                    'new_values' => $user->toArray(),
                    'host'       => 'SYSTEM',
                    'codope'     => 'SOPORTE',
                    'basdat'     => 'chellnova',
                ]
            );

            return $user;
        };

        $users = [
            'soporte' => $makeUser(
                'SOPORTE',
                'Soporte',
                'soporte@restaurant.test'
            ),
            'admin' => $makeUser(
                'ADMIN',
                'Administrador Demo',
                'admin@restaurant.test'
            ),
            'gerente' => $makeUser(
                'GERENTE',
                'Gerente Demo',
                'gerente@restaurant.test'
            ),
            'mesero' => $makeUser(
                'MESERO',
                'Mesero Demo',
                'mesero@restaurant.test'
            ),
        ];

        /*
         * 6. Asignar roles globales.
         */
        foreach ([
            'soporte' => 'SOPORTE',
            'admin' => 'ADMIN',
            'gerente' => 'USER',
            'mesero' => 'USER',
        ] as $userKey => $roleCode) {
            UserRole::firstOrCreate([
                'user_id' => $users[$userKey]->id,
                'role_id' => $roles[$roleCode],
            ]);
        }

        /*
         * 7. Asignar los usuarios al restaurante.
         *
         * Soporte conserva su alcance global y no necesita
         * una asignación operativa al restaurante.
         */
        UserCompanyContext::updateOrCreate(
            [
                'user_id' => $users['admin']->id,
                'company_id' => $company->codmov,
            ],
            [
                'role_id' => $roles['ADMIN'],
                'profile_id' => null,
            ]
        );

        UserCompanyContext::updateOrCreate(
            [
                'user_id' => $users['admin']->id,
                'company_id' => $company2->codmov,
            ],
            [
                'role_id' => $roles['ADMIN'],
                'profile_id' => null,
            ]
        );

        UserCompanyContext::updateOrCreate(
            [
                'user_id' => $users['gerente']->id,
                'company_id' => $company->codmov,
            ],
            [
                'role_id' => $roles['USER'],
                'profile_id' => $gerente->codmov,
            ]
        );

        UserCompanyContext::updateOrCreate(
            [
                'user_id' => $users['mesero']->id,
                'company_id' => $company->codmov,
            ],
            [
                'role_id' => $roles['USER'],
                'profile_id' => $mesero->codmov,
            ]
        );

        $this->command?->info(
            'Seeder ejecutado: Soporte, Administrador, Gerente y Mesero.'
        );

        $this->command?->warn(
            'Contraseña inicial de usuarios nuevos: password. Cámbiala antes de usar estas cuentas.'
        );
    }
}
