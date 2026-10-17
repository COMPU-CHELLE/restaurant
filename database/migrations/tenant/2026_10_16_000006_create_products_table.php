<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('products', function (Blueprint $table) {
            $table->increments('codmov');
            $table->unsignedInteger('category_id');
            $table->string('code')->unique();
            $table->string('barcode', 60)->nullable()->index();
            $table->string('name');
            $table->string('detail')->nullable();
            $table->decimal('sale_price', 15, 2)->default(0);
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->enum('product_type', ['PRODUCT', 'SERVICE'])->default('PRODUCT');
            $table->foreign('category_id')->references('codmov')->on('categories')->restrictOnDelete();
            $table->bigInteger('state')->default(1);

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('products');
    }
};
