<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->create('companies', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code')->unique();
            $table->string('empnom');
            $table->string('empnit');
            $table->string('empdig')->nullable();
            $table->string('empadd')->nullable();
            $table->string('emppho')->nullable();
            $table->string('empema')->nullable();
            $table->string('codcoi', 2)->default('CO');
            // Aprovisionamiento recuperable por etapas
            $table->string('provision_status', 20)->default('pending');
            $table->string('provision_step', 40)->nullable();
            $table->text('provision_error')->nullable();
            $table->timestamp('provisioned_at')->nullable();
            $table->bigInteger('state')->default(1);

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            // Igual que Chellnova: en companies, basdat es el nombre de la base MySQL del restaurante
            // (lo valida TenantManager antes de usarlo).
            $table->string('basdat', 64)->nullable()->unique()->comment('Base de datos del tenant');


            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('companies');
    }
};
