<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('mysql')->create('countries', function (Blueprint $table) {
            $table->increments('codmov');

            // ISO
            $table->string('iso2', 2)->unique();        // CO
            $table->string('iso3', 3)->unique();        // COL
            $table->string('iso_numeric', 3);           // 170
            $table->string('factus_id')->nullable();    //codigo del id en factus

            // Datos básicos
            $table->string('name');                     // Colombia
            $table->string('region')->nullable();       // Americas
            $table->string('subregion')->nullable();    // South America

            // Fiscal / local (opcional por país)
            $table->string('fiscal_code')->nullable();  // DIAN = 170

            $table->bigInteger('state')->default(1);

            // Auditoría
            $table->string('host')->nullable();
            $table->string('codope')->nullable();
            $table->string('basdat')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('countries');
    }
};
