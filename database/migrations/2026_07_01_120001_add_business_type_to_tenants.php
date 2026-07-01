<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona business_type em tenants pra diferenciar MEI de outros tipos
 * de negócio (autônomo, PJ simples, outro) dentro do painel role=common.
 *
 * Backfill: tenants existentes com role=common (ou sem tipo definido) são
 * marcados como 'mei' pra preservar a UX atual — o painel MEI vigente em
 * produção continua funcionando pra eles.
 *
 * Valores conhecidos: 'mei', 'autonomo', 'pj_simples', 'outro'.
 * String (não enum) pra permitir evolução sem migration futura.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('business_type', 20)->nullable()->after('type');
        });

        // Backfill: quem NÃO é ONG hoje é MEI de fato — o painel foi construído
        // pra MEI. Novos tenants não-MEI só existirão a partir de agora.
        DB::table('tenants')
            ->where(function ($q) {
                $q->where('type', '!=', 'ngo')->orWhereNull('type');
            })
            ->update(['business_type' => 'mei']);
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('business_type');
        });
    }
};
