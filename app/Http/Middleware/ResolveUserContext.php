<?php

namespace App\Http\Middleware;

use App\Models\Support\Company;
use App\Models\Support\Role;
use App\Models\Support\UserCompanyContext;
use App\Services\Support\UserContextService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Decide con qué empresa/rol/perfil trabaja el usuario y lo deja en sesión
 * (company_id, role_id, role_code, profile_id), igual que Chellnova.
 */
class ResolveUserContext
{
    protected const COMPANY_KEYS = ['company_id', 'company_name', 'company_code', 'profile_id'];

    protected const CACHE_KEYS = ['menu', 'permissions', 'menu_key'];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $contexts = app(UserContextService::class);

        // SOPORTE (global): puede entrar a cualquier empresa lista.
        if ($supportRole = $contexts->supportRole($user)) {
            $company = ($id = session('soporte_company_id')) ? Company::find($id) : null;

            if ($company && $contexts->isUsable($company)) {
                $this->setContext($company, $supportRole->role, null);
            } else {
                session()->forget('soporte_company_id');
                $this->clearCompany();
                $this->setRole($supportRole->role);
            }

            $this->rememberCompanies($contexts->accessibleCompanies($user));

            return $next($request);
        }

        $assignments = UserCompanyContext::query()
            ->with(['company', 'role'])
            ->where('user_id', $user->id)
            ->get()
            ->filter(fn ($ctx) => $ctx->company && $contexts->isUsable($ctx->company))
            ->values();

        if ($assignments->isEmpty()) {
            abort(403, 'No tienes empresas disponibles. Contacta al administrador.');
        }

        $this->rememberCompanies($assignments->map(fn ($ctx) => [
            'id' => (int) $ctx->company->codmov,
            'name' => $ctx->company->empnom,
            'code' => $ctx->company->code,
        ]));

        $current = session()->has('company_id')
            ? $assignments->firstWhere('company_id', (int) session('company_id'))
            : null;

        if (! $current && $assignments->count() === 1) {
            $current = $assignments->first();
        }

        if ($current) {
            $this->setContext($current->company, $current->role, $current->profile_id);

            return $next($request);
        }

        // Varias empresas y ninguna elegida (o la elegida ya no es válida).
        $this->clearCompany();

        if (! $request->routeIs('company.*')) {
            return redirect()->route('company.select');
        }

        return $next($request);
    }

    protected function setContext(Company $company, Role $role, ?int $profileId): void
    {
        $changed = (int) session('company_id') !== (int) $company->codmov
            || (int) session('role_id') !== (int) $role->codmov
            || (int) session('profile_id') !== (int) $profileId;

        if ($changed) {
            session()->forget(self::CACHE_KEYS);
        }

        session([
            'company_id'   => (int) $company->codmov,
            'company_name' => $company->empnom,
            'company_code' => $company->code,
            'role_id'      => (int) $role->codmov,
            'role_code'    => strtoupper($role->code),
            'role_name'    => $role->name,
            'profile_id'   => $profileId,
        ]);
    }

    /** Empresas a las que puede cambiar desde el selector del sidebar. */
    protected function rememberCompanies(Collection $companies): void
    {
        session(['companies' => $companies
            ->map(fn ($c) => ['id' => $c['id'], 'name' => $c['name'], 'code' => $c['code']])
            ->values()
            ->all()]);
    }

    protected function setRole(Role $role): void
    {
        session([
            'role_id'   => (int) $role->codmov,
            'role_code' => strtoupper($role->code),
            'role_name' => $role->name,
        ]);
    }

    protected function clearCompany(): void
    {
        session()->forget(array_merge(self::COMPANY_KEYS, self::CACHE_KEYS));
    }
}
