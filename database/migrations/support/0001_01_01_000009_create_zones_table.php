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
        Schema::connection('mysql')->create('zones', function (Blueprint $table) {
            $table->increments('codmov');

            $table->string('code')->unique();   // ISO-3166-2 (CO-ANT)
            $table->string('factus_id')->nullable();    //codigo del id en factus
            $table->string('name');
            $table->bigInteger('state')->default(1);

            $table->unsignedInteger('codcoi');        // ISO2 country (CO)
            $table->foreign('codcoi')->references('codmov')->on('countries')->cascadeOnDelete();

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
        Schema::connection('mysql')->dropIfExists('zones');
    }
};
