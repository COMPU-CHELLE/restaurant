<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->create('user_company_context', function (Blueprint $table) {
            $table->increments('codmov');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('role_id');
            $table->unsignedInteger('profile_id')->nullable();  // tenant (sin FK)
            $table->unsignedInteger('branch_id')->nullable();   // reservado
            $table->unsignedInteger('cash_register_id')->nullable(); // caja por defecto del usuario (tenant, sin FK)
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('company_id')->references('codmov')->on('companies')->cascadeOnDelete();
            $table->foreign('role_id')->references('codmov')->on('roles');
            $table->unique(['user_id', 'company_id']);

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('user_company_context');
    }
};
