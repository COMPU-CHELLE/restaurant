<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('printers', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code')->unique();
            $table->string('name');
            $table->string('detail')->nullable();
            $table->string('printer_type', 20)->default('THERMAL');
            $table->string('connection_type', 20)->default('NETWORK');
            $table->string('ip_address', 45)->nullable();
            $table->unsignedInteger('port')->nullable()->default(9100);
            $table->string('paper_width', 10)->default('MM80');
            $table->string('purpose', 20)->default('KITCHEN');
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
        Schema::connection('tenant')->dropIfExists('printers');
    }
};
