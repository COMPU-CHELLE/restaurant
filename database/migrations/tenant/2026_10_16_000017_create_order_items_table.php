<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('order_items', function (Blueprint $table) {
            $table->increments('codmov');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('product_id');
            $table->string('product_name');                      // copia del nombre al momento de la venta
            $table->decimal('quantity', 10, 3)->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->decimal('total', 15, 2);
            $table->string('status', 20)->default('PENDING');
            $table->text('notes')->nullable();
            $table->foreign('order_id')->references('codmov')->on('orders')->cascadeOnDelete();
            $table->foreign('product_id')->references('codmov')->on('products')->restrictOnDelete();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('order_items');
    }
};
