<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// La pantalla inicial es el login: un invitado que entra a /dashboard es enviado a /login.
Route::redirect('/', '/dashboard')->name('home');

// Elegir empresa: solo necesita el contexto del usuario (sin tenant ni permisos).
Route::middleware(['auth', 'verified', 'context'])->prefix('company')->name('company.')->group(function () {
    Route::get('select', [CompanyController::class, 'select'])->name('select');
    Route::post('select/{company}', [CompanyController::class, 'store'])->whereNumber('company')->name('store');
});

// Todo lo operativo: contexto → empresa elegida → base del tenant → menú y permisos.
Route::middleware(['auth', 'verified', 'restaurant'])->group(function () {
    // Un solo /dashboard: según el rol muestra el de soporte, el del administrador o el del usuario.
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

require __DIR__.'/restaurant.php';
require __DIR__.'/settings.php';
