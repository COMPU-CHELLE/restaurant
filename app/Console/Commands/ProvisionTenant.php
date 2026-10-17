<?php

namespace App\Console\Commands;

use App\Models\Support\Company;
use App\Services\Tenant\TenantManager;
use Illuminate\Console\Command;
use Throwable;

class ProvisionTenant extends Command
{
    protected $signature = 'tenant:provision {code : Código de la empresa (companies.code)} {--adopt : Usar la base si ya existe}';

    protected $description = 'Crea (o repara) la base del restaurante: base de datos, migraciones y datos iniciales';

    public function handle(TenantManager $tenants): int
    {
        $company = Company::query()->where('code', $this->argument('code'))->first();

        if (! $company) {
            $this->error('No existe una empresa con ese código.');

            return self::FAILURE;
        }

        try {
            $tenants->provision($company, (bool) $this->option('adopt'));
        } catch (Throwable $e) {
            $this->error("Falló en la etapa [{$company->fresh()->provision_step}]: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Empresa {$company->code} lista. Base: {$company->fresh()->basdat}");

        return self::SUCCESS;
    }
}
