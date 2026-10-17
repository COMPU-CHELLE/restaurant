<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('cash_sessions', function (Blueprint $table) {
            $table->increments('codmov');
            $table->unsignedInteger('cash_register_id');
            $table->unsignedBigInteger('opened_by');            // users.id en la base central (sin FK)
            $table->string('opened_by_name');
            $table->decimal('opening_amount', 15, 2)->default(0);
            $table->decimal('expected_amount', 15, 2)->default(0);
            $table->decimal('closing_amount', 15, 2)->nullable();
            $table->decimal('difference_amount', 15, 2)->nullable();
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->enum('status', ['OPEN', 'CLOSED'])->default('OPEN');
            // = cash_register_id mientras la sesión está abierta, NULL al cerrar.
            // El UNIQUE garantiza a nivel de BD una sola sesión abierta por caja.
            $table->unsignedInteger('open_register_id')->nullable()->unique();
            $table->text('notes')->nullable();
            $table->foreign('cash_register_id')->references('codmov')->on('cash_registers')->restrictOnDelete();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('cash_sessions');
    }
};
