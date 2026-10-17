<?php

namespace App\Http\Middleware;

use App\Models\Tenant\Profile;
use App\Services\Support\PermissionService;
use Closure;
use Illuminate\Http\Request;

/**
 * Carga el menú y los permisos en sesión según el rol:
 * SOPORTE y ADMIN ven todo lo de su ámbito; USER depende de su perfil (tabla profiles del tenant).
 * SOPORTE también tiene menú sin empresa elegida (gestión global).
 */
class LoadUserPermissions
{
    public function handle(Request $request, Closure $next)
    {
        $role = strtoupper((string) session('role_code'));
        $hasCompany = session()->has('company_id');

        if ($role === '' || (! $hasCompany && $role !== 'SOPORTE')) {
            return $next($request);
        }

        $key = implode(':', [session('company_id'), session('role_id'), session('profile_id'), 'v' . config('menu.version', 1)]);

        if (session()->has('menu') && session('menu_key') === $key) {
            return $next($request);
        }

        $service = app(PermissionService::class);

        if (in_array($role, ['SOPORTE', 'ADMIN'], true)) {
            session(['menu' => $service->menu($role, null), 'permissions' => [], 'menu_key' => $key]);

            return $next($request);
        }

        $profile = session('profile_id') ? Profile::query()->where('state', 1)->find(session('profile_id')) : null;

        if (! $profile) {
            abort(403, 'Tu usuario no tiene un perfil activo en esta empresa.');
        }

        $permissions = $service->forProfile((int) $profile->codmov);

        session([
            'menu'        => $service->menu('USER', $permissions),
            'permissions' => $permissions,
            'menu_key'    => $key,
        ]);

        return $next($request);
    }
}
