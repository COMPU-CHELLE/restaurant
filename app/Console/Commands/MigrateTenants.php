<?php

namespace App\Console\Commands;

use App\Models\Support\Company;
use App\Services\Tenant\TenantManager;
use Illuminate\Console\Command;
use Throwable;

class MigrateTenants extends Command
{
    protected $signature = 'tenant:migrate {code? : Código de una empresa (si se omite, todas las que están listas)}';

    protected $description = 'Aplica las migraciones pendientes a las bases de los restaurantes';

    public function handle(TenantManager $tenants): int
    {
        $companies = Company::query()
            ->where('provision_status', 'ready')
            ->whereNotNull('basdat')
            ->when($this->argument('code'), fn ($q, $code) => $q->where('code', $code))
            ->orderBy('code')
            ->get();

        if ($companies->isEmpty()) {
            $this->warn('No hay empresas listas que migrar.');

            return self::FAILURE;
        }

        $failed = 0;

        foreach ($companies as $company) {
            $this->line("» {$company->code} ({$company->basdat})");

            try {
                $this->output->write($tenants->migrate($company->basdat));
            } catch (Throwable $e) {
                $failed++;
                $this->error("  Falló: {$e->getMessage()}");
            }
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
