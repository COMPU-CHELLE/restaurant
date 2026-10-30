<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Una tabla por catálogo (estructura de Chellnova). Generada a partir de config/catalogs.php. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('currencies', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('detail')->nullable();
            $table->string('symbol', 5)->nullable();
            $table->integer('decimals')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
        });

        Schema::connection('tenant')->create('client_tax_types', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->integer('factus_id_v1')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('tax_types', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('tax_rates', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('detail')->nullable();
            $table->string('tax_type', 30)->nullable();
            $table->decimal('percentage', 12, 4)->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
        });

        Schema::connection('tenant')->create('units', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->string('abbreviation', 10)->nullable();
            $table->string('parent_unit', 30)->nullable();
            $table->decimal('factor', 12, 4)->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('document_classes', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->integer('factus_id_v1')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('support_document_classes', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->integer('factus_id_v1')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('organization_types', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('electronic_document_types', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('operation_types', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('credit_note_operation_types', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('debit_note_operation_types', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('credit_note_correction_codes', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('debit_note_correction_codes', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('product_identification_standards', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->integer('legacy_id_v1')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('claim_concepts', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('document_events', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('numbering_range_types', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('support_document_adjustment_reasons', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('surcharge_discount_codes', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('health_coverage_plans', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('health_document_classes', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('health_payment_methods', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('health_advance_concepts', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('health_no_contract_reasons', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('payment_forms', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('payment_means', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('account_types', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('detail')->nullable();
            $table->string('nature', 10)->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
        });

        Schema::connection('tenant')->create('retention_types', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->decimal('percentage', 12, 4)->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('movement_types', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('detail')->nullable();
            $table->string('direction', 10)->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
        });

        Schema::connection('tenant')->create('economic_activities', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('detail')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
        });

        Schema::connection('tenant')->create('pdu_types', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('detail')->nullable();
            $table->string('nature', 10)->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
        });

        Schema::connection('tenant')->create('accounts', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('detail')->nullable();
            $table->string('pdu_type', 30)->nullable();
            $table->integer('level')->nullable();
            $table->string('nature', 10)->nullable();
            $table->string('parent', 20)->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
        });

        Schema::connection('tenant')->create('fiscal_responsibilities', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('code_fe', 20)->nullable()->comment('Código factura electrónica (Factus V2)');
            $table->string('factus_id', 20)->nullable()->comment('ID o código en Factus V1');
            $table->string('name');
            $table->string('detail')->nullable();
            $table->bigInteger('state')->default(1);
            $table->unsignedInteger('source_id')->nullable()->comment('codmov del original en soporte');
            $table->timestamp('imported_at')->nullable();

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->index('code_fe');
            $table->index('factus_id');
        });

        Schema::connection('tenant')->create('order_statuses', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('detail')->nullable();
            $table->string('color', 7)->nullable();
            $table->string('is_final', 10)->nullable();
            $table->bigInteger('state')->default(1);

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
        });

        Schema::connection('tenant')->create('cancellation_reasons', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('detail')->nullable();
            $table->string('requires_note', 10)->nullable();
            $table->bigInteger('state')->default(1);

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
        });

        Schema::connection('tenant')->create('inventory_reasons', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('detail')->nullable();
            $table->string('movement_type', 30)->nullable();
            $table->bigInteger('state')->default(1);

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
        });

        Schema::connection('tenant')->create('preparation_areas', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('detail')->nullable();
            $table->string('printer', 30)->nullable();
            $table->bigInteger('state')->default(1);

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
        });

        Schema::connection('tenant')->create('branches', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('detail')->nullable();
            $table->string('address', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('city', 100)->nullable();
            $table->bigInteger('state')->default(1);

            $table->string('host', 45)->nullable()->comment('Equipo del cliente');
            $table->string('codope', 50)->nullable()->comment('Código del operador');
            $table->string('basdat', 45)->nullable()->comment('Base de datos');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('branches');
        Schema::connection('tenant')->dropIfExists('preparation_areas');
        Schema::connection('tenant')->dropIfExists('inventory_reasons');
        Schema::connection('tenant')->dropIfExists('cancellation_reasons');
        Schema::connection('tenant')->dropIfExists('order_statuses');
        Schema::connection('tenant')->dropIfExists('fiscal_responsibilities');
        Schema::connection('tenant')->dropIfExists('accounts');
        Schema::connection('tenant')->dropIfExists('pdu_types');
        Schema::connection('tenant')->dropIfExists('economic_activities');
        Schema::connection('tenant')->dropIfExists('movement_types');
        Schema::connection('tenant')->dropIfExists('retention_types');
        Schema::connection('tenant')->dropIfExists('account_types');
        Schema::connection('tenant')->dropIfExists('payment_means');
        Schema::connection('tenant')->dropIfExists('payment_forms');
        Schema::connection('tenant')->dropIfExists('health_no_contract_reasons');
        Schema::connection('tenant')->dropIfExists('health_advance_concepts');
        Schema::connection('tenant')->dropIfExists('health_payment_methods');
        Schema::connection('tenant')->dropIfExists('health_document_classes');
        Schema::connection('tenant')->dropIfExists('health_coverage_plans');
        Schema::connection('tenant')->dropIfExists('surcharge_discount_codes');
        Schema::connection('tenant')->dropIfExists('support_document_adjustment_reasons');
        Schema::connection('tenant')->dropIfExists('numbering_range_types');
        Schema::connection('tenant')->dropIfExists('document_events');
        Schema::connection('tenant')->dropIfExists('claim_concepts');
        Schema::connection('tenant')->dropIfExists('product_identification_standards');
        Schema::connection('tenant')->dropIfExists('debit_note_correction_codes');
        Schema::connection('tenant')->dropIfExists('credit_note_correction_codes');
        Schema::connection('tenant')->dropIfExists('debit_note_operation_types');
        Schema::connection('tenant')->dropIfExists('credit_note_operation_types');
        Schema::connection('tenant')->dropIfExists('operation_types');
        Schema::connection('tenant')->dropIfExists('electronic_document_types');
        Schema::connection('tenant')->dropIfExists('organization_types');
        Schema::connection('tenant')->dropIfExists('support_document_classes');
        Schema::connection('tenant')->dropIfExists('document_classes');
        Schema::connection('tenant')->dropIfExists('units');
        Schema::connection('tenant')->dropIfExists('tax_rates');
        Schema::connection('tenant')->dropIfExists('tax_types');
        Schema::connection('tenant')->dropIfExists('client_tax_types');
        Schema::connection('tenant')->dropIfExists('currencies');
    }
};
