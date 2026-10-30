<?php

use App\Http\Controllers\Operation\CashController;
use Illuminate\Support\Facades\Route;

/*
| Operación diaria. Son rutas operativas: el permiso se deduce de la URL y el verbo con el middleware
| "permission". Las acciones de caja usan la última palabra de la URL como acción:
|   POST /operacion/caja/open → operacion.caja.open     POST /operacion/caja/close → …close
|   POST /operacion/caja/movement → …movement           GET  /operacion/caja[/{id}] → …view
*/
Route::middleware(['auth', 'verified', 'restaurant', 'permission'])->group(function () {
    Route::get('operacion/caja', [CashController::class, 'index'])->name('operacion.caja');
    Route::post('operacion/caja/open', [CashController::class, 'open'])->name('operacion.caja.open');
    Route::post('operacion/caja/close', [CashController::class, 'close'])->name('operacion.caja.close');
    Route::post('operacion/caja/movement', [CashController::class, 'movement'])->name('operacion.caja.movement');
    Route::get('operacion/caja/{session}', [CashController::class, 'show'])->whereNumber('session')->name('operacion.caja.show');
});
