<?php

namespace App\Services\Support;

use App\Models\Support\Company;
use App\Models\Support\UserCompanyContext;
use App\Models\Support\UserRole;
use App\Models\User;
use Illuminate\Support\Collection;

class UserContextService
{
    public function supportRole(User $user): ?UserRole
    {
        return UserRole::query()
            ->where('user_id', $user->id)
            ->whereHas('role', fn ($q) => $q->where('code', 'SOPORTE'))
            ->with('role')
            ->first();
    }

    public function isUsable(Company $company): bool
    {
        return (int) $company->state === 1
            && $company->provision_status === 'ready'
            && ! empty($company->basdat);
    }

    /**
     * Empresas a las que el usuario puede entrar.
     *
     * @return Collection<int, array{id:int, name:string, code:string, role:?string}>
     */
    public function accessibleCompanies(User $user): Collection
    {
        if ($this->supportRole($user)) {
            return Company::query()
                ->where('state', 1)
                ->where('provision_status', 'ready')
                ->whereNotNull('basdat')
                ->orderBy('empnom')
                ->get()
                ->map(fn (Company $c) => [
                    'id' => (int) $c->codmov, 'name' => $c->empnom, 'code' => $c->code, 'role' => 'Soporte',
                ])
                ->values();
        }

        return UserCompanyContext::query()
            ->with(['company', 'role'])
            ->where('user_id', $user->id)
            ->get()
            ->filter(fn ($ctx) => $ctx->company && $this->isUsable($ctx->company))
            ->map(fn ($ctx) => [
                'id' => (int) $ctx->company->codmov, 'name' => $ctx->company->empnom,
                'code' => $ctx->company->code, 'role' => $ctx->role?->name,
            ])
            ->values();
    }
}
