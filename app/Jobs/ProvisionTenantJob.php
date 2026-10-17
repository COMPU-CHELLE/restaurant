<?php

namespace App\Jobs;

use App\Models\Support\Company;
use App\Services\Tenant\TenantManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProvisionTenantJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public int $companyId, public bool $adopt = false)
    {
    }

    public function handle(TenantManager $tenants): void
    {
        set_time_limit(0);

        $company = Company::find($this->companyId);

        if (! $company) {
            return;
        }

        try {
            $tenants->provision($company, $this->adopt);
        } catch (Throwable $e) {
            // TenantManager ya dejó el error y la etapa en la empresa; se puede reintentar desde la pantalla.
            Log::warning("Aprovisionamiento de {$company->code} falló: {$e->getMessage()}");
        }
    }
}
