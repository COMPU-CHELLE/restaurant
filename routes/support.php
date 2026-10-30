<?php

use App\Http\Controllers\Support\SupportCompanyController;
use App\Http\Controllers\Support\SupportFactusController;
use App\Http\Controllers\Support\SupportFactusPayController;
use App\Http\Controllers\Support\SupportGeographyController;
use App\Http\Controllers\Support\SupportUserController;
use App\Http\Controllers\Support\SupportTicketController;
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

        Route::get('tickets', [SupportTicketController::class, 'index'])->name('tickets');
        Route::patch('tickets/{ticket}', [SupportTicketController::class, 'update'])->whereNumber('ticket')->name('tickets.update');

        Route::get('usuarios', [SupportUserController::class, 'index'])->name('usuarios');
        Route::post('usuarios', [SupportUserController::class, 'store'])->name('usuarios.store');
        Route::put('usuarios/{user}', [SupportUserController::class, 'update'])->whereNumber('user')->name('usuarios.update');
        Route::patch('usuarios/{user}/toggle', [SupportUserController::class, 'toggle'])->whereNumber('user')->name('usuarios.toggle');

        // Catálogos geográficos alimentados por countries, zones y subzones.
        Route::get('paises', [SupportGeographyController::class, 'countries'])->name('paises');
        Route::post('paises', [SupportGeographyController::class, 'storeCountry'])->name('paises.store');
        Route::put('paises/{country}', [SupportGeographyController::class, 'updateCountry'])->whereNumber('country')->name('paises.update');
        Route::patch('paises/{country}/toggle', [SupportGeographyController::class, 'toggleCountry'])->whereNumber('country')->name('paises.toggle');
        Route::delete('paises/{country}', [SupportGeographyController::class, 'destroyCountry'])->whereNumber('country')->name('paises.destroy');
        Route::get('zonas', [SupportGeographyController::class, 'zones'])->name('zonas');
        Route::post('zonas', [SupportGeographyController::class, 'storeZone'])->name('zonas.store');
        Route::put('zonas/{zone}', [SupportGeographyController::class, 'updateZone'])->whereNumber('zone')->name('zonas.update');
        Route::patch('zonas/{zone}/toggle', [SupportGeographyController::class, 'toggleZone'])->whereNumber('zone')->name('zonas.toggle');
        Route::delete('zonas/{zone}', [SupportGeographyController::class, 'destroyZone'])->whereNumber('zone')->name('zonas.destroy');
        Route::get('subzonas', [SupportGeographyController::class, 'subzones'])->name('subzonas');
        Route::post('subzonas', [SupportGeographyController::class, 'storeSubzone'])->name('subzonas.store');
        Route::put('subzonas/{subzone}', [SupportGeographyController::class, 'updateSubzone'])->whereNumber('subzone')->name('subzonas.update');
        Route::patch('subzonas/{subzone}/toggle', [SupportGeographyController::class, 'toggleSubzone'])->whereNumber('subzone')->name('subzonas.toggle');
        Route::delete('subzonas/{subzone}', [SupportGeographyController::class, 'destroySubzone'])->whereNumber('subzone')->name('subzonas.destroy');

        // Factus: credenciales por empresa y ambiente
        Route::get('factus', [SupportFactusController::class, 'index'])->name('factus');
        Route::get('factus/configuracion', [SupportFactusController::class, 'index'])->name('factus.configuracion');
        Route::post('factus', [SupportFactusController::class, 'store'])->name('factus.store');
        Route::post('factus/test', [SupportFactusController::class, 'test'])->name('factus.test');
        Route::put('factus/{factus}', [SupportFactusController::class, 'update'])->whereNumber('factus')->name('factus.update');
        Route::patch('factus/{factus}/toggle', [SupportFactusController::class, 'toggle'])->whereNumber('factus')->name('factus.toggle');
        Route::post('factus/{factus}/test', [SupportFactusController::class, 'testStored'])->whereNumber('factus')->name('factus.test-stored');

        // Factus Pay (recaudos QR / Bre-B): credenciales por empresa y ambiente
        Route::get('factus-pay', [SupportFactusPayController::class, 'index'])->name('factus-pay');
        Route::post('factus-pay', [SupportFactusPayController::class, 'store'])->name('factus-pay.store');
        Route::post('factus-pay/test', [SupportFactusPayController::class, 'test'])->name('factus-pay.test');
        Route::put('factus-pay/{pay}', [SupportFactusPayController::class, 'update'])->whereNumber('pay')->name('factus-pay.update');
        Route::patch('factus-pay/{pay}/toggle', [SupportFactusPayController::class, 'toggle'])->whereNumber('pay')->name('factus-pay.toggle');
        Route::post('factus-pay/{pay}/test', [SupportFactusPayController::class, 'testStored'])->whereNumber('pay')->name('factus-pay.test-stored');
    });
