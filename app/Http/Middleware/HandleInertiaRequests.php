<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * IMPORTANTE: este método se ejecuta ANTES de la cadena de middleware de la ruta
     * (contexto → empresa → tenant → permisos). Por eso lo que depende de la sesión
     * del contexto va en closures: Inertia las evalúa al armar la respuesta, cuando
     * la sesión ya tiene la empresa, el rol y el menú de ESTA petición.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',

            // Contexto multi-tenant (lo arma ResolveUserContext en la sesión).
            'company' => fn () => session()->has('company_id') ? [
                'id' => (int) session('company_id'),
                'name' => session('company_name'),
                'code' => session('company_code'),
            ] : null,
            'role' => fn () => session('role_code') ? [
                'code' => strtoupper((string) session('role_code')),
                'name' => session('role_name'),
            ] : null,
            'companies' => fn () => session()->has('company_id') ? session('companies', []) : [],
            'companiesCount' => fn () => session()->has('company_id') ? count(session('companies', [])) : 0,
            'menu' => fn () => session('menu', []),
            // null = sin restricciones (ADMIN / SOPORTE); lista de nombres para el rol USER.
            'permissions' => fn () => in_array(strtoupper((string) session('role_code')), ['ADMIN', 'SOPORTE'], true)
                ? null
                : array_values(array_column(session('permissions', []), 'name')),
        ];
    }
}
