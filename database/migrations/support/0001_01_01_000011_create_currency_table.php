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
        Schema::connection('mysql')->create('currencies', function (Blueprint $table) {
            $table->increments('codmov');

            $table->string('code')->unique();   // ISO 4217 (USD, COP)
            $table->string('name');
            $table->string('symbol', 10);
            $table->string('detail')->nullable();

            $table->unsignedInteger('codcoi');        // ISO2 country
            $table->foreign('codcoi')->references('codmov')->on('countries')->cascadeOnDelete();

            $table->bigInteger('state')->default(1);

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
        Schema::connection('mysql')->dropIfExists('currency');
    }
};
