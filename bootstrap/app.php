<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnsureCompanySelected;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\LoadUserPermissions;
use App\Http\Middleware\ResolveUserContext;
use App\Http\Middleware\SetTenantConnection;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'context' => ResolveUserContext::class,
            'company.selected' => EnsureCompanySelected::class,
            'tenant' => SetTenantConnection::class,
            'load.permissions' => LoadUserPermissions::class,
            'permission' => CheckPermission::class,
            'role' => EnsureRole::class,
        ]);

        // Cadena multi-tenant (el orden importa).
        $middleware->group('restaurant', [
            ResolveUserContext::class,
            EnsureCompanySelected::class,
            SetTenantConnection::class,
            LoadUserPermissions::class,
        ]);

        // Gestión global de soporte: no exige empresa ni base de tenant.
        $middleware->group('soporte', [
            ResolveUserContext::class,
            LoadUserPermissions::class,
        ]);
        // El binding implicito de modelos del tenant (p. ej. Area $area) necesita la base ya elegida:
        // la cadena multi-tenant debe correr ANTES que SubstituteBindings.
        if (method_exists($middleware, 'prependToPriorityList')) {
            $middleware->prependToPriorityList(\Illuminate\Routing\Middleware\SubstituteBindings::class, ResolveUserContext::class);
            $middleware->appendToPriorityList(ResolveUserContext::class, EnsureCompanySelected::class);
            $middleware->appendToPriorityList(EnsureCompanySelected::class, SetTenantConnection::class);
            $middleware->appendToPriorityList(SetTenantConnection::class, LoadUserPermissions::class);
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
