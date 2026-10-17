<?php

namespace App\Services\Support;

use App\Jobs\ProvisionTenantJob;
use App\Models\Support\Company;
use App\Services\BaseService;

class CompanyService extends BaseService
{
    protected string $model = Company::class;

    protected string $label = 'Empresa';

    protected array $searchable = ['code', 'empnom', 'empnit', 'basdat'];

    protected array $sortable = ['codmov', 'code', 'empnom', 'empnit', 'state'];

    protected array $fields = ['code', 'empnom', 'empnit', 'empdig', 'empadd', 'emppho', 'empema', 'country_code'];

    protected bool $audited = true;

    /** Fila que ve la pantalla de empresas. */
    public function toRow(Company $company): array
    {
        return [
            'id' => (int) $company->codmov,
            'code' => $company->code,
            'name' => $company->empnom,
            'nit' => $company->empnit,
            'dv' => $company->empdig,
            'address' => $company->empadd,
            'phone' => $company->emppho,
            'email' => $company->empema,
            'country' => $company->country_code,
            'database' => $company->basdat,
            'state' => (int) $company->state,
            'provision_status' => $company->provision_status,
            'provision_step' => $company->provision_step,
            'provision_error' => $company->provision_error,
            'provisioned_at' => $company->provisioned_at?->toIso8601String(),
        ];
    }

    /**
     * Pide crear la base del restaurante (base de datos + migraciones + datos iniciales).
     * Corre en segundo plano; si QUEUE_CONNECTION=sync se ejecuta en esta misma petición.
     */
    public function provision(Company $company, bool $adopt = false): array
    {
        if (in_array($company->provision_status, ['queued', 'running'], true)) {
            return ['data' => $company, 'flash' => $this->toast('La empresa ya se está aprovisionando.', 'warning')];
        }

        if ($company->provision_status === 'ready') {
            return ['data' => $company, 'flash' => $this->toast('La empresa ya está lista.', 'info')];
        }

        $company->forceFill(['provision_status' => 'queued', 'provision_step' => null, 'provision_error' => null])->save();

        ProvisionTenantJob::dispatch((int) $company->codmov, $adopt);

        return ['data' => $company, 'flash' => $this->toast('Aprovisionamiento iniciado. Puede tardar unos segundos.', 'info')];
    }
}
