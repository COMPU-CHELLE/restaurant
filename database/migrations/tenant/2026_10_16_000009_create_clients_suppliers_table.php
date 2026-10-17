<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('clients_suppliers', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code')->unique();
            $table->string('document_type', 10)->default('CC');
            $table->string('document_number', 30);
            $table->string('business_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('mobile', 30)->nullable();
            $table->string('address')->nullable();
            $table->boolean('customer')->default(true);
            $table->boolean('supplier')->default(false);
            $table->index(['document_type', 'document_number']);
            $table->bigInteger('state')->default(1);

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('clients_suppliers');
    }
};
