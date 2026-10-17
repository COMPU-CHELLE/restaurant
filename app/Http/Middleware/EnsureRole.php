<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Limita una sección completa a ciertos roles:
 *   ->middleware('role:SOPORTE')        solo soporte
 *   ->middleware('role:ADMIN,SOPORTE')  administrador de empresa y soporte
 * Va DESPUÉS de ResolveUserContext (que es quien deja role_code en la sesión).
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $allowed = array_map('strtoupper', $roles);

        if (! in_array(strtoupper((string) session('role_code')), $allowed, true)) {
            abort(403, 'No tienes acceso a esta sección.');
        }

        return $next($request);
    }
}
