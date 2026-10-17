<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->create('factus_services', function (Blueprint $table) {
            $table->increments('codmov');
            $table->unsignedInteger('codemp');
            $table->enum('environment', ['sandbox', 'production'])->default('production');
            $table->string('base_url');                       // https://api.factus.com.co
            $table->string('api_version', 10)->default('v1'); // v1 | v2
            $table->string('client_id');
            // Se guardan cifrados (cast "encrypted" del modelo con APP_KEY): por eso son text.
            $table->text('client_secret');
            $table->string('username');
            $table->text('password');
            $table->text('token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();

            $table->boolean('active')->default(true);
            // = 1 solo mientras la configuración está activa, NULL si no. El UNIQUE garantiza UNA activa
            // por empresa y ambiente y permite todas las inactivas que se quieran (en Chellnova el UNIQUE
            // incluía "active" y limitaba también las inactivas).
            $table->unsignedTinyInteger('active_key')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('codemp')->references('codmov')->on('companies')->restrictOnDelete();
            $table->unique(['codemp', 'environment', 'active_key']);
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('factus_services');
    }
};
