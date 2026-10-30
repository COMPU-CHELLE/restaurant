<?php

namespace App\Providers;

use App\Listeners\LogAuthenticationEvents;
use App\Models\User;
use App\Observers\SoftLogObserver;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Activa la bitácora: observa todos los modelos de Support y Tenant (menos la propia bitácora)
 * y el usuario, y escucha los eventos de autenticación.
 */
class AuditServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach (['Support', 'Tenant'] as $namespace) {
            foreach (glob(app_path("Models/{$namespace}/*.php")) ?: [] as $file) {
                $class = "App\\Models\\{$namespace}\\" . basename($file, '.php');

                if ($class !== 'App\\Models\\Support\\Soflog' && class_exists($class)) {
                    $class::observe(SoftLogObserver::class);
                }
            }
        }

        User::observe(SoftLogObserver::class);

        Event::listen(Login::class, [LogAuthenticationEvents::class, 'onLogin']);
        Event::listen(Logout::class, [LogAuthenticationEvents::class, 'onLogout']);
        Event::listen(Failed::class, [LogAuthenticationEvents::class, 'onFailed']);
    }
}
