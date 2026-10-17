<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('orders', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code')->unique();
            $table->unsignedInteger('restaurant_table_id')->nullable();
            $table->unsignedInteger('cash_session_id')->nullable();
            $table->unsignedInteger('contact_id')->nullable();
            $table->enum('status', ['OPEN', 'PAYMENT_PENDING', 'PAID', 'CANCELLED'])->default('OPEN');
            $table->unsignedInteger('occupied_seats')->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->dateTime('ordered_at');
            $table->dateTime('closed_at')->nullable();
            $table->unsignedBigInteger('opened_by')->nullable();
            $table->string('opened_by_name')->nullable();
            $table->index(['restaurant_table_id', 'status']);
            $table->foreign('restaurant_table_id')->references('codmov')->on('restaurant_tables')->nullOnDelete();
            $table->foreign('cash_session_id')->references('codmov')->on('cash_sessions')->nullOnDelete();
            $table->foreign('contact_id')->references('codmov')->on('clients_suppliers')->nullOnDelete();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('orders');
    }
};
