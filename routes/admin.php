<?php

use App\Http\Controllers\Admin\AdminCollectionController;
use App\Http\Controllers\Admin\AdminCompanyController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;

/*
| Administración de la empresa activa (ADMIN; SOPORTE también, dentro de una empresa).
| Las URLs coinciden con los niveles del árbol: /admin/empresa, /admin/usuarios y /admin/perfiles.
| Los modelos del tenant (perfiles) llegan como id y se cargan en el controlador: el binding implícito
| ocurre antes de elegir la base del tenant.
*/
Route::middleware(['auth', 'verified', 'restaurant', 'role:ADMIN,SOPORTE'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('empresa', [AdminCompanyController::class, 'show'])->name('empresa');
        Route::post('empresa', [AdminCompanyController::class, 'update'])->name('empresa.update');

        Route::get('usuarios', [AdminUserController::class, 'index'])->name('usuarios');
        Route::post('usuarios', [AdminUserController::class, 'store'])->name('usuarios.store');
        Route::put('usuarios/{user}', [AdminUserController::class, 'update'])->whereNumber('user')->name('usuarios.update');
        Route::patch('usuarios/{user}/toggle', [AdminUserController::class, 'toggle'])->whereNumber('user')->name('usuarios.toggle');
        Route::delete('usuarios/{user}', [AdminUserController::class, 'destroy'])->whereNumber('user')->name('usuarios.destroy');

        Route::get('perfiles', [AdminProfileController::class, 'index'])->name('perfiles');
        Route::post('perfiles', [AdminProfileController::class, 'store'])->name('perfiles.store');
        Route::put('perfiles/{profile}', [AdminProfileController::class, 'update'])->whereNumber('profile')->name('perfiles.update');
        Route::patch('perfiles/{profile}/toggle', [AdminProfileController::class, 'toggle'])->whereNumber('profile')->name('perfiles.toggle');
        Route::delete('perfiles/{profile}', [AdminProfileController::class, 'destroy'])->whereNumber('profile')->name('perfiles.destroy');

        // Recaudos de Factus Pay (QR / Bre-B)
        Route::get('recaudos', [AdminCollectionController::class, 'index'])->name('recaudos');
        Route::post('recaudos/{collection}/refresh', [AdminCollectionController::class, 'refresh'])->whereNumber('collection')->name('recaudos.refresh');
    });
