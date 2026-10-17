<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('profile_permissions', function (Blueprint $table) {
            $table->increments('codmov');
            $table->unsignedInteger('profile_id');
            $table->unsignedInteger('permission_id');   // permissions.codmov en la base central (sin FK)
            $table->foreign('profile_id')->references('codmov')->on('profiles')->cascadeOnDelete();
            $table->unique(['profile_id', 'permission_id']);

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('profile_permissions');
    }
};
