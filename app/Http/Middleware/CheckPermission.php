<?php

namespace App\Http\Middleware;

use App\Models\Support\Level;
use App\Models\Support\Module;
use Closure;
use Illuminate\Http\Request;

/**
 * Igual que Chellnova: el permiso sale de la URL (nivel del árbol) + verbo HTTP.
 *   GET → view, POST → store, PUT/PATCH → update, DELETE → delete,
 *   y una palabra de acción al final de la URL (pay, open, close, ...) la reemplaza.
 * También acepta un permiso explícito:  ->middleware('permission:operacion.caja.open')
 *
 * Diferencia con Chellnova: si la ruta no coincide con ningún nivel del árbol se NIEGA
 * (allá se dejaba pasar). Una ruta pública para usuarios con empresa simplemente no lleva este middleware.
 */
class CheckPermission
{
    protected array $actionWords = [
        'toggle', 'restore', 'force', 'print',
        'layout', 'pay', 'move', 'split', 'open', 'close', 'movement', 'prices', 'images',
    ];

    public function handle(Request $request, Closure $next, ?string $permission = null)
    {
        if (in_array(strtoupper((string) session('role_code')), ['SOPORTE', 'ADMIN'], true)) {
            return $next($request);
        }

        $granted = collect(session('permissions', []));

        if ($permission !== null) {
            $permission = strtolower($permission);

            if ($granted->contains(fn ($p) => ($p['name'] ?? '') === $permission)) {
                return $next($request);
            }

            abort(403, 'No tienes permiso para realizar esta acción.');
        }

        $context = $this->resolveContextFromPath($request);

        if (! $context) {
            abort(403, 'Esta ruta no tiene un permiso definido.');
        }

        [$codmod, $codniv, $action] = $context;

        $allowed = $granted->contains(fn ($p) => str_ends_with((string) ($p['name'] ?? ''), ".{$action}")
            && (int) ($p['codmod'] ?? 0) === $codmod
            && (int) ($p['codniv'] ?? 0) === $codniv);

        if (! $allowed) {
            abort(403, 'No tienes permiso para realizar esta acción.');
        }

        return $next($request);
    }

    /** @return array{0:int,1:int,2:string}|null  [codmod, codniv, acción] */
    protected function resolveContextFromPath(Request $request): ?array
    {
        $segments = array_values(array_filter(explode('/', trim($request->path(), '/'))));

        $action = null;
        if ($segments && in_array(end($segments), $this->actionWords, true)) {
            $action = array_pop($segments);
        }

        if ($segments && ctype_digit((string) end($segments))) {
            array_pop($segments);
        }

        if (! $segments) {
            return null;
        }

        $action ??= match ($request->method()) {
            'GET', 'HEAD'  => 'view',
            'POST'         => 'store',
            'PUT', 'PATCH' => 'update',
            'DELETE'       => 'delete',
            default        => null,
        };

        if (! $action) {
            return null;
        }

        $basePath = '/' . implode('/', $segments);

        if ($level = Level::where('url', $basePath)->where('type', 'LEVEL')->first()) {
            $module = Module::where('code', $level->parent)->first();

            return $module ? [(int) $module->codmov, (int) $level->codmov, $action] : null;
        }

        if ($module = Module::where('url', $basePath)->first()) {
            return [(int) $module->codmov, 0, $action];
        }

        return null;
    }
}
