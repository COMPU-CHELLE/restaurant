<?php

namespace App\Services\Tenant;

use App\Models\Support\Company;
use App\Models\Tenant\Companie;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Misma idea que el TenantManager de Chellnova (conexión 'tenant' con database dinámica y
 * 'mysql_admin' para CREATE DATABASE), con tres endurecimientos:
 *  - el nombre de la base se valida siempre (nada de SQL con nombres sin escapar);
 *  - el aprovisionamiento es por etapas y deja el estado en la empresa (se puede reintentar);
 *  - no adopta una base que ya existe sin que se pida expresamente.
 */
class TenantManager
{
    public const NAME_PATTERN = '/^[A-Za-z][A-Za-z0-9_]{0,63}$/';

    public function assertValidName(string $database): void
    {
        if (! preg_match(self::NAME_PATTERN, $database)) {
            throw new InvalidArgumentException('Nombre de base de datos no válido.');
        }
    }

    public function setTenantConnection(string $database): void
    {
        $this->assertValidName($database);

        Config::set('database.connections.tenant.database', $database);
        DB::purge('tenant');
        DB::reconnect('tenant');
    }

    public function databaseExists(string $database): bool
    {
        $this->assertValidName($database);

        return DB::connection('mysql_admin')
            ->table('information_schema.SCHEMATA')
            ->where('SCHEMA_NAME', $database)
            ->exists();
    }

    public function createDatabase(string $database): void
    {
        $this->assertValidName($database); // el patrón impide comillas y espacios

        DB::connection('mysql_admin')->statement(
            'CREATE DATABASE `' . $database . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
    }


    public function migrate(string $database): string
    {
        $this->setTenantConnection($database);

        $exitCode = Artisan::call('migrate', [
            '--path' => 'database/migrations/tenant',
            '--database' => 'tenant',
            '--force' => true,
        ]);

        $output = Artisan::output();

        if ($exitCode !== 0) {
            throw new RuntimeException(
                "Fallaron las migraciones de la base {$database}:\n{$output}"
            );
        }

        return $output;
    }

    public function databaseNameFor(Company $company): string
    {
        $slug = strtolower(preg_replace('/[^A-Za-z0-9]/', '', (string) $company->code));

        return 'rest_' . substr($slug !== '' ? $slug : (string) $company->codmov, 0, 40);
    }

    /**
     * Crea la base, migra y deja los datos iniciales. Se puede volver a ejecutar si falla.
     */
    public function provision(Company $company, bool $adopt = false): Company
    {
        $company->forceFill(['provision_status' => 'running', 'provision_error' => null])->save();

        try {
            $database = $company->basdat ?: $this->databaseNameFor($company);
            $this->assertValidName($database);

            $inUse = Company::query()
                ->where('basdat', $database)
                ->where('codmov', '!=', $company->codmov)
                ->exists();

            if ($inUse) {
                throw new RuntimeException("La base {$database} ya pertenece a otra empresa.");
            }

            $this->stage($company, 'database');
            if ($this->databaseExists($database)) {
                if (! $company->basdat && ! $adopt) {
                    throw new RuntimeException(
                        "La base {$database} ya existe y no está asignada a esta empresa. Usa --adopt si es la correcta."
                    );
                }
            } else {
                $this->createDatabase($database);
            }
            $company->forceFill(['basdat' => $database])->save();

            $this->stage($company, 'migrate');
            $this->migrate($database);

            $this->stage($company, 'seed');
            $this->seedInitialData($company);

            $company->forceFill([
                'provision_status' => 'ready',
                'provision_step'   => 'done',
                'provision_error'  => null,
                'provisioned_at'   => now(),
            ])->save();
        } catch (Throwable $e) {
            $company->forceFill([
                'provision_status' => 'failed',
                'provision_error'  => mb_substr($e->getMessage(), 0, 2000),
            ])->save();

            throw $e;
        }

        return $company;
    }

    protected function stage(Company $company, string $step): void
    {
        $company->forceFill(['provision_step' => $step])->save();
    }

    protected function seedInitialData(Company $company): void
    {
        if (Companie::query()->exists()) {
            return;
        }

        Companie::query()->create([
            'restaurant_name' => $company->empnom,
            'business_name'   => $company->empnom,
            'tax_id'          => trim($company->empnit . ($company->empdig ? '-' . $company->empdig : '')),
            'phone'           => $company->emppho,
            'email'           => $company->empema,
            'address'         => $company->empadd,
            'country'         => $company->country_code,
            'state'           => 1,
        ]);
    }
}
