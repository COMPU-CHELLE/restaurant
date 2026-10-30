<?php

use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void {
        Schema::connection('mysql')->create('factus_pay_services', function(Blueprint $table) {
            $table->increments('codmov');
            $table->unsignedInteger('codemp');
            $table->enum('environment',['sandbox','production'])->default('sandbox');
            $table->string('base_url');
            $table->string('email');
            $table->text('password');
            $table->text('token')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('codemp')->references('codmov')->on('companies')->cascadeOnDelete();
            $table->unique(['codemp','environment']);
        });
    }

    public function down(): void {
        Schema::connection('mysql')->dropIfExists('factus_pay_services');
    }
};
