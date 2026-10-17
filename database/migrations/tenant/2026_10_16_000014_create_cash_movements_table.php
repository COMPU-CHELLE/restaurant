<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('cash_movements', function (Blueprint $table) {
            $table->increments('codmov');
            $table->unsignedInteger('cash_session_id');
            $table->unsignedInteger('payment_method_id')->nullable();
            $table->enum('movement_type', ['INCOME', 'EXPENSE']);
            $table->decimal('amount', 15, 2);
            $table->string('concept');
            $table->string('detail')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name')->nullable();
            $table->foreign('cash_session_id')->references('codmov')->on('cash_sessions')->restrictOnDelete();
            $table->foreign('payment_method_id')->references('codmov')->on('payment_methods')->nullOnDelete();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('cash_movements');
    }
};
