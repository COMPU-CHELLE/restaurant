<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Configuration\AreaController;

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
        '/soporte/parametros' => 'Parámetros',
        '/soporte/bitacora' => 'Bitácora global',
    ] as $url => $title) {
        $place($url, $title);
    }
});

// ---- ADMIN: administración de la empresa activa
Route::middleware(['auth', 'verified', 'restaurant', 'role:ADMIN,SOPORTE'])->group(function () use ($place) {
    foreach ([
        '/admin/empresa' => 'Datos de la empresa',
        '/admin/usuarios' => 'Usuarios',
        '/admin/perfiles' => 'Perfiles',
        '/admin/parametros' => 'Parámetros',
        '/admin/bitacora' => 'Bitácora de la empresa',
        '/admin/configuracion/cajas' => 'Cajas registradoras',
        '/admin/configuracion/metodos-pago' => 'Métodos de pago',
        '/admin/configuracion/impresoras' => 'Impresoras',
        '/admin/configuracion/tipos-movimiento' => 'Tipos de movimientos',
        '/admin/configuracion/tipos-cuenta' => 'Tipos de cuentas',
        '/admin/configuracion/impuestos' => 'Impuestos',
        '/admin/configuracion/tipos-documento' => 'Tipos de documento',
        '/admin/configuracion/estados-pedido' => 'Estados de pedidos',
        '/admin/configuracion/motivos-anulacion' => 'Motivos de anulación',
        '/admin/configuracion/unidades' => 'Unidades de medida',
        '/admin/configuracion/motivos-inventario' => 'Motivos de inventario',
        '/admin/configuracion/areas-preparacion' => 'Áreas de preparación',
    ] as $url => $title) {
        $place($url, $title);
    }
});

// ---- ADMIN: editor de zonas y mesas
Route::middleware(['auth', 'verified', 'restaurant', 'role:ADMIN,SOPORTE'])
    ->prefix('configuracion/areas')->name('admin.areas.')
    ->controller(AreaController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'storeArea')->name('store');
        Route::put('/{area}', 'updateArea')->whereNumber('area')->name('update');
        Route::delete('/{area}', 'destroyArea')->whereNumber('area')->name('destroy');
        Route::post('/{area}/mesas', 'storeTable')->whereNumber('area')->name('tables.store');
        Route::put('/{area}/mesas/{table}', 'updateTable')->whereNumber('area')->whereNumber('table')->name('tables.update');
        Route::delete('/{area}/mesas/{table}', 'destroyTable')->whereNumber('area')->whereNumber('table')->name('tables.destroy');
    });

Route::middleware(['auth', 'verified', 'restaurant', 'role:ADMIN,SOPORTE'])
    ->get('/admin/configuracion/areas', fn () => redirect('/configuracion/areas'));

// ---- OPERATIVO: según el perfil del usuario
Route::middleware(['auth', 'verified', 'restaurant'])->group(function () use ($place) {
    foreach ([
        '/operacion/mesas' => 'Mesas',
        '/operacion/ordenes' => 'Órdenes',
        '/operacion/caja' => 'Caja',
        '/catalogo/productos' => 'Productos',
        '/catalogo/categorias' => 'Categorías',
        '/inventario/movimientos' => 'Movimientos de inventario',
        '/terceros/contactos' => 'Clientes y proveedores',
        '/reportes/ventas' => 'Ventas',
    ] as $url => $title) {
        $place($url, $title)->middleware('permission');
    }
});

// ---- SOPORTE: pantallas reales (empresas y usuarios)
require __DIR__.'/support.php';
