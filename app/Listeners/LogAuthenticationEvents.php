<?php

namespace App\Listeners;

use App\Services\Audit\Audit;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Laravel\Fortify\Fortify;

/** Inicios y cierres de sesión e intentos fallidos. Nunca registra contraseñas. */
class LogAuthenticationEvents
{
    public function onLogin(Login $event): void
    {
        Audit::record('LOGIN', 'AUTH', "Inicio de sesión del usuario {$event->user->code}", operator: $event->user->code);
    }

    public function onLogout(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        Audit::record('LOGOUT', 'AUTH', "Cierre de sesión del usuario {$event->user->code}", operator: $event->user->code);
    }

    public function onFailed(Failed $event): void
    {
        $attempted = (string) ($event->credentials[Fortify::username()] ?? $event->user?->code ?? 'desconocido');

        Audit::record(
            'LOGIN_FAILED',
            'AUTH',
            'Intento de inicio de sesión fallido para: ' . mb_substr($attempted, 0, 60),
            operator: $event->user?->code ?? 'ANONIMO',
        );
    }
}
