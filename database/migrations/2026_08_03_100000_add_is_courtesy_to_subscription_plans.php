<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Flag "plano cortesia" — tenants nesse plano não geram invoices
 * automaticamente e veem banner verde "Plano Cortesia" em /minha-conta/faturas
 * em vez da tabela de cobranças.
 *
 * Casos de uso: parcerias, tenants demo, ONGs apadrinhadas, contas do time.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->boolean('is_courtesy')->default(false)->after('is_active')
                  ->comment('Se true, tenants no plano não são cobrados nem geram invoices automáticas.');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('is_courtesy');
        });
    }
};
