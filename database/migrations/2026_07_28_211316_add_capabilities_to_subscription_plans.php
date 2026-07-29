<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloco 3 — feature flags técnicas por plano.
 *
 * `features` (existente) = array de strings marketing (ex: "IA Ilimitada").
 * `capabilities` (novo)  = mapa key=>bool de capabilities técnicas
 *                          (ex: {"whatsapp_cloud": true, "social_posts": false}).
 *
 * Default null = grace period: plano sem capabilities definidas mantém
 * TUDO liberado (backward compat com planos legados). Só bloqueia quem
 * for EXPLICITAMENTE marcado com key => false.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->json('capabilities')->nullable()->after('features');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('capabilities');
        });
    }
};
