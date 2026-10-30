<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('factus_pay_collections', function (Blueprint $table) {
            $table->increments('codmov');
            $table->unsignedInteger('order_id');
            $table->string('reference_code', 60)->unique();      // REST-{empresa}-{orden}
            $table->string('factus_collection_id', 80)->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('method', 10)->default('QR');          // QR | BREB
            $table->string('status', 20)->default('pending');     // pending | paid | expired | failed | cancelled
            $table->text('qr')->nullable();
            $table->text('qr_image')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('last_checked_at')->nullable();
            $table->unsignedInteger('payment_id')->nullable();    // se llena al conciliar con payments
            $table->string('environment', 12);
            $table->json('response')->nullable();
            $table->text('error')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');
            $table->timestamps();

            $table->foreign('order_id')->references('codmov')->on('orders')->restrictOnDelete();
            $table->foreign('payment_id')->references('codmov')->on('payments')->nullOnDelete();
            $table->index(['status', 'created_at']);
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('factus_pay_collections');
    }
};
