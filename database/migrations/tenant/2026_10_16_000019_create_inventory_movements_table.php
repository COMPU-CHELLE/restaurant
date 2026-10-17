<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('inventory_movements', function (Blueprint $table) {
            $table->increments('codmov');
            $table->unsignedInteger('product_id');
            $table->enum('movement_type', ['PURCHASE', 'SALE', 'ADJUSTMENT', 'WASTE', 'RETURN', 'PRODUCTION']);
            $table->decimal('quantity', 15, 3);                  // con signo: entra (+) / sale (-)
            $table->string('reference_type', 30)->default('MANUAL');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name')->nullable();
            $table->index(['product_id', 'created_at']);
            $table->foreign('product_id')->references('codmov')->on('products')->restrictOnDelete();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('inventory_movements');
    }
};
