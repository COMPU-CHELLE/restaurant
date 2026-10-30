<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->create('soflog', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('tipeve', 20);          // CREATE, UPDATE, DELETE, RESTORE, LOGIN, LOGOUT, LOGIN_FAILED
            $table->string('detail', 500);
            $table->string('codmod', 60);          // entidad o módulo auditado (COMPANY, PROFILE, AUTH…)
            $table->string('period', 8);           // entidad o módulo auditado (COMPANY, PROFILE, AUTH…)
            $table->string('anopro', 6);           // entidad o módulo auditado (COMPANY, PROFILE, AUTH…)
            $table->string('mespro', 6);           // entidad o módulo auditado (COMPANY, PROFILE, AUTH…)
            $table->string('diapro', 6);           // AAAAMM
            $table->date('fecope');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->unsignedInteger('codemp')->nullable();   // empresa activa al momento del evento
            $table->string('host', 45)->nullable()->comment('IP del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 64)->nullable()->comment('Base de datos afectada');
            $table->timestamps();

            $table->foreign('codemp')->references('codmov')->on('companies')->nullOnDelete();
            $table->index(['codemp', 'created_at']);
            $table->index('tipeve');
            $table->index('codope');
            $table->index('period');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('soflog');
    }
};
