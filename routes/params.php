<?php

use App\Http\Controllers\Audit\AdminSoflogController;
use App\Http\Controllers\Audit\SupportSoflogController;
use App\Http\Controllers\Support\AdminTicketController;
use App\Http\Controllers\Support\SupportPermissionController;
use App\Http\Controllers\Catalog\AdminCatalogController;
use App\Http\Controllers\Catalog\SupportCatalogController;
use Illuminate\Support\Facades\Route;

/*
| Parámetros (catálogos) y Bitácora.
|  Soporte → /soporte/parametros y /soporte/bitacora   (global)
|  Admin   → /admin/parametros   y /admin/bitacora     (solo la empresa activa)
| {type} es la clave de un catálogo de config/catalogs.php; los elementos de la empresa se cargan por id
| en el controlador (el binding implícito ocurre antes de elegir la base del tenant).
*/
Route::middleware(['auth', 'verified', 'soporte', 'role:SOPORTE'])
    ->prefix('soporte')
    ->name('soporte.')
    ->group(function () {
        Route::get('parametros', [SupportCatalogController::class, 'index'])->name('parametros');
        Route::get('parametros/permisos', [SupportPermissionController::class, 'index'])->name('parametros.permisos');
        Route::post('parametros/permisos', [SupportPermissionController::class, 'store'])->name('parametros.permisos.store');
        Route::put('parametros/permisos/{permission}', [SupportPermissionController::class, 'update'])->whereNumber('permission')->name('parametros.permisos.update');
        Route::patch('parametros/permisos/{permission}/toggle', [SupportPermissionController::class, 'toggle'])->whereNumber('permission')->name('parametros.permisos.toggle');
        Route::delete('parametros/permisos/{permission}', [SupportPermissionController::class, 'destroy'])->whereNumber('permission')->name('parametros.permisos.destroy');
        Route::get('parametros/{type}', [SupportCatalogController::class, 'index'])->where('type', '[a-z_]+')->name('parametros.show');
        Route::post('parametros/{type}', [SupportCatalogController::class, 'store'])->where('type', '[a-z_]+')->name('parametros.store');
        Route::put('parametros/{type}/{item}', [SupportCatalogController::class, 'update'])->where('type', '[a-z_]+')->whereNumber('item')->name('parametros.update');
        Route::patch('parametros/{type}/{item}/toggle', [SupportCatalogController::class, 'toggle'])->where('type', '[a-z_]+')->whereNumber('item')->name('parametros.toggle');
        Route::delete('parametros/{type}/{item}', [SupportCatalogController::class, 'destroy'])->where('type', '[a-z_]+')->whereNumber('item')->name('parametros.destroy');

        Route::get('bitacora', [SupportSoflogController::class, 'index'])->name('bitacora');
    });

Route::middleware(['auth', 'verified', 'restaurant', 'role:ADMIN,SOPORTE'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Solo administradores de empresa pueden crear y consultar sus tickets.
        Route::get('tickets', [AdminTicketController::class, 'index'])->middleware('role:ADMIN')->name('tickets');
        Route::post('tickets', [AdminTicketController::class, 'store'])->middleware('role:ADMIN')->name('tickets.store');

        Route::get('parametros', [AdminCatalogController::class, 'index'])->name('parametros');
        Route::get('parametros/{type}', [AdminCatalogController::class, 'index'])->where('type', '[a-z_]+')->name('parametros.show');
        Route::post('parametros/import-all', [AdminCatalogController::class, 'importAll'])->name('parametros.import-all');
        Route::post('parametros/{type}/import', [AdminCatalogController::class, 'import'])->where('type', '[a-z_]+')->name('parametros.import');
        Route::post('parametros/{type}', [AdminCatalogController::class, 'store'])->where('type', '[a-z_]+')->name('parametros.store');
        Route::put('parametros/{type}/{item}', [AdminCatalogController::class, 'update'])->where('type', '[a-z_]+')->whereNumber('item')->name('parametros.update');
        Route::patch('parametros/{type}/{item}/toggle', [AdminCatalogController::class, 'toggle'])->where('type', '[a-z_]+')->whereNumber('item')->name('parametros.toggle');
        Route::delete('parametros/{type}/{item}', [AdminCatalogController::class, 'destroy'])->where('type', '[a-z_]+')->whereNumber('item')->name('parametros.destroy');

        Route::get('bitacora', [AdminSoflogController::class, 'index'])->name('bitacora');
    });
