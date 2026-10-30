<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->create('permissions', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code')->unique();
            $table->string('name');              // slug-de-la-url.accion  (ej. operacion.mesas.view)
            $table->string('detail')->nullable();
            $table->unsignedInteger('codmod');             // módulo
            $table->unsignedInteger('codniv')->nullable(); // nivel
            $table->unsignedInteger('codite')->nullable(); // ítem

            $table->foreign('codmod')->references('codmov')->on('modules')->restrictOnDelete();
            $table->foreign('codniv')->references('codmov')->on('levels')->restrictOnDelete();
            $table->foreign('codite')->references('codmov')->on('levels')->restrictOnDelete();
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
        Schema::connection('mysql')->dropIfExists('permissions');
    }
};
