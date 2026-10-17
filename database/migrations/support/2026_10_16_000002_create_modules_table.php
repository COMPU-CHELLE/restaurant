<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->create('modules', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code')->unique();
            $table->string('name');
            $table->string('detail')->nullable();
            $table->string('url')->nullable();
            $table->string('icon')->nullable();
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
        Schema::connection('mysql')->dropIfExists('modules');
    }
};
