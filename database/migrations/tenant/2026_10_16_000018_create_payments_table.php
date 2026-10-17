<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('payments', function (Blueprint $table) {
            $table->increments('codmov');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('payment_method_id');
            $table->unsignedInteger('cash_session_id');
            $table->decimal('amount', 15, 2);                    // valor aplicado a la cuenta
            $table->decimal('surcharge_amount', 15, 2)->default(0);
            $table->decimal('total_charged', 15, 2);             // amount + recargo
            $table->string('reference', 60)->nullable();
            $table->dateTime('paid_at');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name')->nullable();
            $table->foreign('order_id')->references('codmov')->on('orders')->restrictOnDelete();
            $table->foreign('payment_method_id')->references('codmov')->on('payment_methods')->restrictOnDelete();
            $table->foreign('cash_session_id')->references('codmov')->on('cash_sessions')->restrictOnDelete();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('payments');
    }
};
