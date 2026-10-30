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
        Schema::connection('mysql')->create('subzones', function (Blueprint $table) {
            $table->increments('codmov');

            $table->string('code')->unique();   // CO-ANT-MEDELLIN
            $table->string('factus_id')->nullable();    //codigo del id en factus
            $table->string('factus_code')->nullable();
            $table->string('iso_numeric')->nullable();
            $table->string('name');
            $table->bigInteger('state')->default(1);

            $table->unsignedInteger('codzon');           // CO-ANT
            $table->foreign('codzon')->references('codmov')->on('zones')->cascadeOnDelete();

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
        Schema::connection('mysql')->dropIfExists('subzones');
    }
};
