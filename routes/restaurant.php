<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Páginas provisionales de los módulos
|--------------------------------------------------------------------------
| Cada URL coincide con un nivel del árbol (SupportSecuritySeeder). Las siguientes fases
| reemplazan cada una por su controlador real.
|
|  /soporte/*  → solo SOPORTE, sin empresa (base support)
|  /admin/*    → ADMIN de la empresa (y SOPORTE dentro de una empresa)
|  el resto    → operativo: el permiso se deduce de la URL (middleware "permission")
*/

$place = fn (string $url, string $title) => Route::inertia($url, 'module-placeholder', ['title' => $title])
    ->name(str_replace('/', '.', trim($url, '/')));

// ---- SOPORTE: gestión global
Route::middleware(['auth', 'verified', 'soporte', 'role:SOPORTE'])->group(function () use ($place) {
    foreach ([
        '/soporte/facturas' => 'Facturas',
        '/soporte/factus/historial' => 'Historial de emitidos y errores',
        '/soporte/factus/consumo' => 'Consumo vs plan adquirido',
    ] as $url => $title) {
        $place($url, $title);
    }
});

// ---- ADMIN: administración de la empresa activa
Route::middleware(['auth', 'verified', 'restaurant', 'role:ADMIN,SOPORTE'])->group(function () use ($place) {
    foreach ([
        '/admin/asignar' => 'Asignar permisos',
        '/admin/facturas' => 'Facturas',
        '/admin/impresiones/comandas' => 'Comandas',
    ] as $url => $title) {
        $place($url, $title);
    }
});

// ---- OPERATIVO: según el perfil del usuario
Route::middleware(['auth', 'verified', 'restaurant'])->group(function () use ($place) {
    foreach ([
        '/operacion/mesas' => 'Mesas',
        '/operacion/ordenes' => 'Órdenes',
        '/inventario/movimientos' => 'Movimientos de inventario',
        '/reportes/ventas' => 'Ventas',
    ] as $url => $title) {
        $place($url, $title)->middleware('permission');
    }
});

// ---- Integración Factus Pay (recaudos QR). La confirmación no marca la orden pagada automáticamente:
// el estado debe verificarse en la API antes de registrar el pago en caja.
Route::middleware(['auth', 'verified', 'restaurant'])->prefix('operacion/factus-pay')->name('factus-pay.')->group(function () {
    Route::post('recaudos', [\App\Http\Controllers\FactusPayController::class, 'create'])->name('collections.create');
    Route::get('recaudos/{reference}', [\App\Http\Controllers\FactusPayController::class, 'status'])->name('collections.status');
});

// Emisión de factura electrónica desde una orden pagada.
Route::middleware(['auth', 'verified', 'restaurant'])->prefix('operacion/ordenes')->name('orders.')->group(function () {
    Route::post('{order}/factus/emitir', [\App\Http\Controllers\RestaurantFactusController::class, 'emit'])->whereNumber('order')->name('factus.emit');
});

// ---- SOPORTE: pantallas reales (empresas y usuarios)
require __DIR__.'/support.php';

// ---- ADMIN: pantallas reales (empresa, usuarios y perfiles)
require __DIR__.'/admin.php';

// ---- Parámetros (catálogos) y Bitácora de soporte y de administración
require __DIR__.'/params.php';

// ---- Fase 7: catalog
require __DIR__.'/catalog.php';

// ---- Fase 7: areas
require __DIR__.'/areas.php';

// ---- Fase 8: config
require __DIR__.'/config.php';

// ---- Fase 9: operation
require __DIR__.'/operation.php';
