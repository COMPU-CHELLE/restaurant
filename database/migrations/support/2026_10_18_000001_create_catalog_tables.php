<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Una tabla por catálogo (estructura de Chellnova). Generada a partir de config/catalogs.php.
 *
 * Campos Factus:
 *  - factus_id   => id numérico de la API v1 de Factus (en métodos/formas de pago v1 usa el mismo código)
 *  - factus_code => código DIAN que usa la API v2 de Factus
 */
return new class extends Migration
{
    public function up(): void
    {
        // ===== Plan contable primero: otras tablas dependen de `accounts` =====
        Schema::connection('mysql')->create('account_pdu', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 10)->unique();
            $table->string('name', 100);
            $table->string('detail')->nullable();
            $table->enum('nature', ['D', 'C'])->nullable(); // Naturaleza por defecto (D=Débito, C=Crédito)
            $table->bigInteger('state')->default(1);

            $table->string('host')->nullable();
            $table->string('codope')->nullable();
            $table->string('basdat')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql')->create('accounts', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 20);
            $table->string('name', 150);
            $table->tinyInteger('level'); // 1=Clase, 2=Grupo, 3=Cuenta, 4=Subcuenta
            $table->unsignedInteger('codcta')->nullable();
            $table->unsignedInteger('codpdu')->nullable();
            $table->enum('nature', ['D', 'C']);

            $table->bigInteger('regter')->default(0);
            $table->bigInteger('regcos')->default(0);
            $table->bigInteger('regrte')->default(0);
            $table->bigInteger('regiva')->default(0);
            $table->bigInteger('regica')->default(0);

            $table->bigInteger('state')->default(1);
            $table->string('host')->nullable();
            $table->string('codope')->nullable();
            $table->string('basdat')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('codcta')->references('codmov')->on('accounts')->onDelete('restrict');
            $table->foreign('codpdu')->references('codmov')->on('account_pdu')->onDelete('restrict');
        });

        // ===== Clases de documento (tipos de documento de identidad) =====
        Schema::connection('mysql')->create('document_classes', function (Blueprint $table) {
            $table->increments('codmov');

            $table->string('code')->nullable();          // Código local: NIT, CC, PAS...
            $table->string('factus_code')->nullable();   // Código DIAN / Factus v2 (13, 31, 41...)
            $table->string('factus_id')->nullable();     // Id Factus v1 (1..11)
            $table->string('name');
            $table->string('detail')->nullable();

            $table->bigInteger('state')->default(1);

            $table->string('host')->nullable();
            $table->string('codope')->nullable();
            $table->string('basdat')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('factus_code');
        });

        // ===== Unidades de medida =====
        Schema::connection('mysql')->create('units', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code')->nullable();          // Código local: UND, KG...
            $table->string('factus_code')->nullable();   // Código UN/CEFACT (94, KGM, LBR...)
            $table->string('factus_id')->nullable();     // Id Factus v1 (70, 414...)
            $table->string('name');
            $table->string('detail')->nullable();
            $table->bigInteger('state')->default(1);

            $table->unsignedInteger('parent_unit_id')->nullable()->comment('Unidad base de la que depende');
            $table->decimal('factor', 12, 4)->default(1)->comment('Equivalencia respecto a la unidad base');

            $table->string('host')->nullable()->comment('Equipo del cliente');
            $table->string('codope')->nullable()->comment('Código del operador');
            $table->string('basdat')->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->softDeletes();

            $table->index('factus_code');
            $table->foreign('parent_unit_id')->references('codmov')->on('units')->onDelete('restrict');
        });

        // ===== Formas / métodos de pago =====
        Schema::connection('mysql')->create('payment_forms', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code')->unique();
            $table->string('name')->unique();
            $table->string('factus_code')->nullable();   // Código DIAN / Factus v2
            $table->string('factus_id')->nullable();     // Id/código Factus v1
            $table->string('detail')->nullable();
            $table->bigInteger('state')->default(1);
            $table->bigInteger('forpay')->default(1)->comment('1 = método de pago, 0 = forma de pago (contado/crédito)');

            $table->string('host')->nullable()->comment('Equipo del cliente');
            $table->string('codope')->nullable()->comment('Código del operador');
            $table->string('basdat')->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql')->create('payment_method_commissions', function (Blueprint $table) {
            $table->increments('codmov');
            $table->unsignedInteger('codfor');
            $table->string('name');
            $table->string('provee')->nullable();
            $table->decimal('porcom', 8, 4)->nullable();
            $table->decimal('fixcom', 14, 2)->nullable();
            $table->decimal('fixfee', 14, 2)->nullable();
            $table->boolean('useiva')->default(false);
            $table->unsignedInteger('ctacom')->nullable();
            $table->unsignedInteger('ctacru')->nullable();
            $table->bigInteger('state')->default(1);
            $table->string('host')->nullable();
            $table->string('codope')->nullable();
            $table->string('basdat')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('codfor')->references('codmov')->on('payment_forms');
            $table->foreign('ctacom')->references('codmov')->on('accounts')->onDelete('restrict');
            $table->foreign('ctacru')->references('codmov')->on('accounts')->onDelete('restrict');
        });

        Schema::connection('mysql')->create('economic_activity', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code');
            $table->string('code_fe')->nullable();
            $table->string('name');
            $table->string('detail')->nullable();
            $table->decimal('porcen')->default(0);
            $table->decimal('porret')->default(0);
            $table->decimal('porcre')->default(0);

            $table->unsignedInteger('codcta')->nullable();
            $table->unsignedInteger('ctaret')->nullable();
            $table->unsignedInteger('ctacre')->nullable();

            $table->bigInteger('conret')->default(1);
            $table->bigInteger('tipreg')->default(0);
            $table->bigInteger('state')->default(1);

            $table->foreign('codcta')->references('codmov')->on('accounts')->cascadeOnDelete();
            $table->foreign('ctaret')->references('codmov')->on('accounts')->cascadeOnDelete();
            $table->foreign('ctacre')->references('codmov')->on('accounts')->cascadeOnDelete();

            $table->string('host')->nullable()->comment('Equipo del cliente');
            $table->string('codope')->nullable()->comment('Código del operador');
            $table->string('basdat')->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql')->create('inventory_types', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code')->unique();
            $table->string('name')->unique();
            $table->string('detail')->nullable();
            $table->unsignedInteger('codmod')->nullable();
            $table->bigInteger('costo')->default(0)->comment('Afecta costo');
            $table->bigInteger('trasla')->default(0)->comment('Es traslado');
            $table->bigInteger('movimi')->default(0)->comment('0 ninguno, 1 entrada, 2 salida, 3 traslado');
            $table->bigInteger('state')->default(1);
            $table->string('host')->nullable()->comment('Equipo del cliente');
            $table->string('codope')->nullable()->comment('Código del operador');
            $table->string('basdat')->nullable()->comment('Base de datos');

            $table->foreign('codmod')->references('codmov')->on('modules')->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql')->create('account_types', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code')->unique();
            $table->string('name')->unique();
            $table->string('detail')->nullable();
            $table->unsignedInteger('codmod')->nullable();
            $table->string('nature')->nullable()->comment('Naturaleza financiera');
            $table->bigInteger('state')->default(1);
            $table->string('host')->nullable()->comment('Equipo del cliente');
            $table->string('codope')->nullable()->comment('Código del operador');
            $table->string('basdat')->nullable()->comment('Base de datos');

            $table->foreign('codmod')->references('codmov')->on('modules')->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql')->create('retention_types', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('code', 20);
            $table->string('name');
            $table->string('detail')->nullable();
            $table->unsignedInteger('codcta')->nullable();
            $table->decimal('porrte', 5, 2)->nullable();
            $table->decimal('poriva', 5, 2)->nullable();
            $table->decimal('basrte', 15, 2)->nullable();
            $table->tinyInteger('state')->default(1);
            $table->tinyInteger('tipcer')->default(0);
            $table->tinyInteger('gencer')->default(1);
            $table->unsignedInteger('codcoi');
            $table->foreign('codcta')->references('codmov')->on('accounts')->cascadeOnDelete();

            $table->string('host')->nullable()->comment('Equipo del cliente');
            $table->string('codope')->nullable()->comment('Código del operador');
            $table->string('basdat')->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->softDeletes();
        });

        // Catálogo genérico: tipos de organización, tributos de cliente, impuestos...
        // Los ids/códigos de Factus van en `extra` => {"factus_id": "..", "factus_code": ".."}
        Schema::connection('mysql')->create('catalog_items', function (Blueprint $table) {
            $table->increments('codmov');
            $table->string('type', 40);
            $table->string('code', 30);
            $table->string('name');
            $table->string('detail')->nullable();
            $table->json('extra');
            $table->tinyInteger('state')->default(1);

            $table->string('host')->nullable()->comment('Equipo del cliente');
            $table->string('codope')->nullable()->comment('Código del operador');
            $table->string('basdat')->nullable()->comment('Base de datos');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        // Inverso a las dependencias: primero lo que referencia, al final accounts y account_pdu
        Schema::connection('mysql')->dropIfExists('catalog_items');
        Schema::connection('mysql')->dropIfExists('retention_types');
        Schema::connection('mysql')->dropIfExists('account_types');
        Schema::connection('mysql')->dropIfExists('inventory_types');
        Schema::connection('mysql')->dropIfExists('economic_activity');
        Schema::connection('mysql')->dropIfExists('payment_method_commissions');
        Schema::connection('mysql')->dropIfExists('payment_forms');
        Schema::connection('mysql')->dropIfExists('units');
        Schema::connection('mysql')->dropIfExists('document_class');
        Schema::connection('mysql')->dropIfExists('accounts');
        Schema::connection('mysql')->dropIfExists('account_pdu');
    }
};
