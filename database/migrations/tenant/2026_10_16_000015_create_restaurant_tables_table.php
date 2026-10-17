<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('restaurant_tables', function (Blueprint $table) {
            $table->increments('codmov');
            $table->unsignedInteger('area_id');
            $table->unsignedInteger('number');
            $table->string('name')->nullable();
            $table->unsignedInteger('capacity')->default(4);
            $table->enum('shape', ['ROUND', 'RECTANGLE', 'SQUARE', 'OVAL'])->default('RECTANGLE');
            $table->unsignedInteger('occupied_seats')->default(0);
            $table->integer('pos_x')->default(0);
            $table->integer('pos_y')->default(0);
            $table->integer('rotation')->default(0);
            $table->unsignedInteger('width')->default(100);
            $table->unsignedInteger('height')->default(100);
            $table->enum('status', ['AVAILABLE', 'OCCUPIED', 'RESERVED', 'PAYMENT_PENDING', 'DIRTY', 'OUT_OF_SERVICE'])->default('AVAILABLE');
            $table->unique(['area_id', 'number']);
            $table->foreign('area_id')->references('codmov')->on('areas')->restrictOnDelete();
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
        Schema::connection('tenant')->dropIfExists('restaurant_tables');
    }
};
