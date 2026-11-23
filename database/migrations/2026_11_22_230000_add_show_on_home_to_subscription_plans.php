<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plano ativo pode ficar fora da home do site (venda só por link direto ou
 * pela equipe). Planos existentes continuam aparecendo (default true).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('subscription_plans', 'show_on_home')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->boolean('show_on_home')->default(true)->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('subscription_plans', 'show_on_home')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->dropColumn('show_on_home');
            });
        }
    }
};
