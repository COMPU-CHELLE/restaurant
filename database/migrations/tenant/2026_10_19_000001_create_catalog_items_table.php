<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Parámetros de ESTA empresa: propios o importados desde soporte (source_id = id del original).
        Schema::connection('tenant')->create('catalog_items', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('type', 40);
            $table->string('code', 30);
            $table->string('name');
            $table->string('detail')->nullable();
            $table->json('extra')->nullable();      // campos propios de cada catálogo (ver config/catalogs.php)
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable();   // catalog_items.codmov de la base support
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->timestamps();

            $table->unique(['type', 'code']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('catalog_items');
    }
};
