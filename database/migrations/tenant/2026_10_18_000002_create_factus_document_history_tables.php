<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->create('factus_documents_requests', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('provider')->default('factus');
            $table->string('document_type');
            $table->string('document_table')->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->unsignedBigInteger('document_id_factus')->nullable();
            $table->string('reference_code')->nullable();
            $table->string('document_number')->nullable();
            $table->text('qr')->nullable();
            $table->string('cufe')->nullable();
            $table->text('public_url')->nullable();
            $table->text('qr_image')->nullable();
            $table->string('status')->nullable();
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->text('error')->nullable();
            $table->string('environment');
            $table->string('endpoint');
            $table->unsignedSmallInteger('http_code')->nullable();
            $table->string('host',45)->nullable();
            $table->string('codope',50)->nullable();
            $table->string('basdat',45)->nullable();
            $table->timestamps();
            $table->index('reference_code');
            $table->index('document_number');
            $table->index(['document_table','document_id']);
        });
        Schema::connection('tenant')->create('factus_documents_events', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('document_type')->nullable();
            $table->string('document_table')->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->unsignedBigInteger('document_id_factus')->nullable();
            $table->string('reference_code')->nullable();
            $table->string('document_number');
            $table->string('cude')->nullable();
            $table->string('event_code');
            $table->string('event_name');
            $table->date('effective_date')->nullable();
            $table->time('effective_time')->nullable();
            $table->json('payload')->nullable();
            $table->string('environment')->nullable();
            $table->timestamps();
            $table->index('document_number');
            $table->index('event_code');
            $table->index('reference_code');
            $table->index('document_id');
        });
    }
    public function down(): void { Schema::connection('tenant')->dropIfExists('factus_documents_events'); Schema::connection('tenant')->dropIfExists('factus_documents_requests'); }
};
