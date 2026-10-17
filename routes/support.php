<?php

use App\Http\Controllers\Support\SupportCompanyController;
use App\Http\Controllers\Support\SupportFactusController;
use App\Http\Controllers\Support\SupportUserController;
use Illuminate\Support\Facades\Route;

/*
| Gestión global de soporte (base support, sin empresa elegida).
| Las URLs coinciden con los niveles del árbol: /soporte/empresas y /soporte/usuarios.
*/
Route::middleware(['auth', 'verified', 'soporte', 'role:SOPORTE'])
    ->prefix('soporte')
    ->name('soporte.')
    ->group(function () {
        Route::get('empresas', [SupportCompanyController::class, 'index'])->name('empresas');
        Route::post('empresas', [SupportCompanyController::class, 'store'])->name('empresas.store');
        Route::put('empresas/{company}', [SupportCompanyController::class, 'update'])->whereNumber('company')->name('empresas.update');
        Route::patch('empresas/{company}/toggle', [SupportCompanyController::class, 'toggle'])->whereNumber('company')->name('empresas.toggle');
        Route::post('empresas/{company}/provision', [SupportCompanyController::class, 'provision'])->whereNumber('company')->name('empresas.provision');

        Route::get('usuarios', [SupportUserController::class, 'index'])->name('usuarios');
        Route::post('usuarios', [SupportUserController::class, 'store'])->name('usuarios.store');
        Route::put('usuarios/{user}', [SupportUserController::class, 'update'])->whereNumber('user')->name('usuarios.update');
        Route::patch('usuarios/{user}/toggle', [SupportUserController::class, 'toggle'])->whereNumber('user')->name('usuarios.toggle');

        // Factus: credenciales por empresa y ambiente
        Route::get('factus', [SupportFactusController::class, 'index'])->name('factus');
        Route::post('factus', [SupportFactusController::class, 'store'])->name('factus.store');
        Route::post('factus/test', [SupportFactusController::class, 'test'])->name('factus.test');
        Route::put('factus/{factus}', [SupportFactusController::class, 'update'])->whereNumber('factus')->name('factus.update');
        Route::patch('factus/{factus}/toggle', [SupportFactusController::class, 'toggle'])->whereNumber('factus')->name('factus.toggle');
        Route::post('factus/{factus}/test', [SupportFactusController::class, 'testStored'])->whereNumber('factus')->name('factus.test-stored');
    });
