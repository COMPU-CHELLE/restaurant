<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('companie', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('restaurant_name');
            $table->string('business_name')->nullable();
            $table->string('tax_id', 30)->nullable();
            $table->string('invoice_prefix', 10)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('currency', 3)->default('COP');
            $table->string('timezone', 50)->default('America/Bogota');
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->string('logo', 500)->nullable();
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
        Schema::connection('tenant')->dropIfExists('companie');
    }
};
