<?php

namespace App\Http\Middleware;

use App\Models\Support\Company;
use App\Services\Support\UserContextService;
use App\Services\Tenant\TenantManager;
use Closure;
use Illuminate\Http\Request;
use InvalidArgumentException;

class SetTenantConnection
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user() || ! session()->has('company_id')) {
            return $next($request);
        }

        $company = Company::find(session('company_id'));

        if (! $company || ! app(UserContextService::class)->isUsable($company)) {
            session()->forget(['company_id', 'company_name', 'company_code', 'profile_id', 'menu', 'permissions', 'menu_key']);
            abort(403, 'La empresa no está disponible.');
        }

        try {
            app(TenantManager::class)->setTenantConnection($company->basdat);
        } catch (InvalidArgumentException) {
            abort(403, 'La empresa tiene una base de datos no válida.');
        }

        return $next($request);
    }
}
