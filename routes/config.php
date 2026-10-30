<?php

use App\Http\Controllers\Admin\Configuration\CashRegisterController;
use App\Http\Controllers\Admin\Configuration\PaymentMethodController;
use App\Http\Controllers\Admin\Configuration\PrinterController;
use Illuminate\Support\Facades\Route;

/*
| Configuración operativa de la empresa: cajas registradoras, métodos de pago e impresoras.
| Cada una tiene su propia tabla en la base del tenant (cash_registers, payment_methods, printers).
*/
Route::middleware(['auth', 'verified', 'restaurant', 'role:ADMIN,SOPORTE'])->group(function () {
    Route::prefix('admin/configuracion/cajas')->name('admin.configuracion.cajas.')->group(function () {
        Route::get('/', [CashRegisterController::class, 'index'])->name('index');
        Route::post('/', [CashRegisterController::class, 'store'])->name('store');
        Route::put('{register}', [CashRegisterController::class, 'update'])->whereNumber('register')->name('update');
        Route::patch('{register}/toggle', [CashRegisterController::class, 'toggle'])->whereNumber('register')->name('toggle');
        Route::delete('{register}', [CashRegisterController::class, 'destroy'])->whereNumber('register')->name('destroy');
    });

    Route::prefix('admin/configuracion/metodos-pago')->name('admin.configuracion.metodos-pago.')->group(function () {
        Route::get('/', [PaymentMethodController::class, 'index'])->name('index');
        Route::post('/', [PaymentMethodController::class, 'store'])->name('store');
        Route::put('{method}', [PaymentMethodController::class, 'update'])->whereNumber('method')->name('update');
        Route::patch('{method}/toggle', [PaymentMethodController::class, 'toggle'])->whereNumber('method')->name('toggle');
        Route::delete('{method}', [PaymentMethodController::class, 'destroy'])->whereNumber('method')->name('destroy');
    });

    Route::prefix('admin/impresiones/impresoras')->name('admin.impresiones.impresoras.')->group(function () {
        Route::get('/', [PrinterController::class, 'index'])->name('index');
        Route::post('/', [PrinterController::class, 'store'])->name('store');
        Route::put('{printer}', [PrinterController::class, 'update'])->whereNumber('printer')->name('update');
        Route::patch('{printer}/toggle', [PrinterController::class, 'toggle'])->whereNumber('printer')->name('toggle');
        Route::delete('{printer}', [PrinterController::class, 'destroy'])->whereNumber('printer')->name('destroy');
    });
});
