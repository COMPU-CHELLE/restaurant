<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('product_prices', function (Blueprint $table) {
            $table->increments('codmov');
            $table->unsignedInteger('product_id');
            $table->enum('price_type', ['SALE', 'COST']);
            $table->decimal('price', 15, 2);
            $table->dateTime('effective_from');
            $table->dateTime('effective_to')->nullable();
            $table->boolean('active')->default(true);
            $table->foreign('product_id')->references('codmov')->on('products')->cascadeOnDelete();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('product_prices');
    }
};
