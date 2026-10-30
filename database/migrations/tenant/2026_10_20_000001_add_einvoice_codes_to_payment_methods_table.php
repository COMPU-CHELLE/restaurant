<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('tenant');

        if (! $schema->hasColumn('payment_methods', 'payment_means_code')) {
            $schema->table('payment_methods', function (Blueprint $table) {
                // Medio de pago DIAN (parámetro "Medios de pago": 10 efectivo, 48 tarjeta crédito…).
                $table->string('payment_means_code', 10)->nullable()->after('surcharge_percentage');
                // Forma de pago DIAN (parámetro "Formas de pago": 1 contado, 2 crédito).
                $table->string('payment_form_code', 10)->default('1')->after('payment_means_code');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('tenant');

        if ($schema->hasColumn('payment_methods', 'payment_means_code')) {
            $schema->table('payment_methods', function (Blueprint $table) {
                $table->dropColumn(['payment_means_code', 'payment_form_code']);
            });
        }
    }
};
