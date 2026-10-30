<?php

use App\Http\Controllers\Admin\Configuration\AreaController;
use Illuminate\Support\Facades\Route;

/*
| Salones y áreas (con el plano de mesas): administración de la empresa.
| Usa binding implícito de modelos del tenant (Area $area, RestaurantTable $table), que requiere que la
| cadena multi-tenant corra antes que SubstituteBindings (ver bootstrap/app.php).
*/
Route::middleware(['auth', 'verified', 'restaurant', 'role:ADMIN,SOPORTE'])
    ->prefix('admin/configuracion/areas')
    ->name('admin.configuracion.areas.')
    ->group(function () {
        Route::get('/', [AreaController::class, 'index'])->name('index');
        Route::post('/', [AreaController::class, 'storeArea'])->name('store');
        Route::put('{area}', [AreaController::class, 'updateArea'])->whereNumber('area')->name('update');
        Route::delete('{area}', [AreaController::class, 'destroyArea'])->whereNumber('area')->name('destroy');
        Route::post('{area}/mesas', [AreaController::class, 'storeTable'])->whereNumber('area')->name('tables.store');
        Route::put('{area}/mesas/{table}', [AreaController::class, 'updateTable'])->whereNumber(['area', 'table'])->name('tables.update');
        Route::delete('{area}/mesas/{table}', [AreaController::class, 'destroyTable'])->whereNumber(['area', 'table'])->name('tables.destroy');
    });
