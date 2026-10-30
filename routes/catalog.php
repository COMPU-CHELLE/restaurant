<?php

use App\Http\Controllers\Catalog\CategoryController;
use App\Http\Controllers\Catalog\ProductController;
use App\Http\Controllers\Third\ContactController;
use Illuminate\Support\Facades\Route;

/*
| Catálogo (categorías y productos) y Terceros (clientes y proveedores). Son operativos: el permiso se deduce
| de la URL y el verbo (…view, …store, …update, …toggle, …delete) con el middleware "permission". Las acciones
| sobre un producto (precios, imágenes) usan la última palabra de la URL (…/prices, …/images) como acción.
| Las rutas cuyo final es un id (borrar o marcar una imagen) llevan el permiso explícito.
*/
Route::middleware(['auth', 'verified', 'restaurant'])->group(function () {
    Route::middleware('permission')->group(function () {
        // Categorías
        Route::get('catalogo/categorias', [CategoryController::class, 'index'])->name('catalogo.categorias');
        Route::post('catalogo/categorias', [CategoryController::class, 'store'])->name('catalogo.categorias.store');
        Route::put('catalogo/categorias/{category}', [CategoryController::class, 'update'])->whereNumber('category')->name('catalogo.categorias.update');
        Route::patch('catalogo/categorias/{category}/toggle', [CategoryController::class, 'toggle'])->whereNumber('category')->name('catalogo.categorias.toggle');
        Route::delete('catalogo/categorias/{category}', [CategoryController::class, 'destroy'])->whereNumber('category')->name('catalogo.categorias.destroy');

        // Productos
        Route::get('catalogo/productos', [ProductController::class, 'index'])->name('catalogo.productos');
        Route::post('catalogo/productos', [ProductController::class, 'store'])->name('catalogo.productos.store');
        Route::put('catalogo/productos/{product}', [ProductController::class, 'update'])->whereNumber('product')->name('catalogo.productos.update');
        Route::patch('catalogo/productos/{product}/toggle', [ProductController::class, 'toggle'])->whereNumber('product')->name('catalogo.productos.toggle');
        Route::delete('catalogo/productos/{product}', [ProductController::class, 'destroy'])->whereNumber('product')->name('catalogo.productos.destroy');
        Route::post('catalogo/productos/{product}/prices', [ProductController::class, 'addPrice'])->whereNumber('product')->name('catalogo.productos.prices');
        Route::post('catalogo/productos/{product}/images', [ProductController::class, 'uploadImages'])->whereNumber('product')->name('catalogo.productos.images');

        // Terceros
        Route::get('terceros/contactos', [ContactController::class, 'index'])->name('terceros.contactos');
        Route::post('terceros/contactos', [ContactController::class, 'store'])->name('terceros.contactos.store');
        Route::put('terceros/contactos/{contact}', [ContactController::class, 'update'])->whereNumber('contact')->name('terceros.contactos.update');
        Route::patch('terceros/contactos/{contact}/toggle', [ContactController::class, 'toggle'])->whereNumber('contact')->name('terceros.contactos.toggle');
        Route::delete('terceros/contactos/{contact}', [ContactController::class, 'destroy'])->whereNumber('contact')->name('terceros.contactos.destroy');
    });

    // Detalle e imágenes de un producto: permiso explícito (la URL no coincide con un nivel del árbol).
    Route::get('catalogo/productos/{product}/detail', [ProductController::class, 'detail'])
        ->whereNumber('product')->middleware('permission:catalogo.productos.view')->name('catalogo.productos.detail');
    Route::patch('catalogo/productos/{product}/images/{image}/primary', [ProductController::class, 'primaryImage'])
        ->whereNumber(['product', 'image'])->middleware('permission:catalogo.productos.images')->name('catalogo.productos.images.primary');
    Route::delete('catalogo/productos/{product}/images/{image}', [ProductController::class, 'destroyImage'])
        ->whereNumber(['product', 'image'])->middleware('permission:catalogo.productos.images')->name('catalogo.productos.images.destroy');
});
