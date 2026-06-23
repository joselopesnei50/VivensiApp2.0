<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cozinha Solidária — Fase 0 (ativação por tenant, §3.7).
 *
 * `enabled_modules` é o lugar canônico de "configuração de perfil" (a tabela
 * tenant_operational_profiles já existe e é usada pra categoria/instrucao do
 * Bruce). Adicionar uma coluna nova em `tenants` seria mais cara — esta opção
 * é menor blast radius.
 *
 * Exemplo de valor:
 *   {"cozinha_solidaria": true}
 *
 * O painel do Terceiro Setor consulta esse JSON para mostrar ou esconder os
 * menus da Cozinha.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('tenant_operational_profiles')) {
            return;
        }
        if (Schema::hasColumn('tenant_operational_profiles', 'enabled_modules')) {
            return;
        }

        Schema::table('tenant_operational_profiles', function (Blueprint $table) {
            $table->json('enabled_modules')->nullable()->after('vocabulario');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tenant_operational_profiles')) {
            return;
        }
        if (!Schema::hasColumn('tenant_operational_profiles', 'enabled_modules')) {
            return;
        }

        Schema::table('tenant_operational_profiles', function (Blueprint $table) {
            $table->dropColumn('enabled_modules');
        });
    }
};
