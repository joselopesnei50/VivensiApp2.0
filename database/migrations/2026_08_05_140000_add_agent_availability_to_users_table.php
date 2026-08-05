<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P2 (2026-08-05) — sinal de disponibilidade do agente de atendimento.
 *
 * `agent_availability` e informativo — a UI de transferencia mostra a
 * bolinha de status ao lado do nome do agente. Nao bloqueia transferencia
 * (gestor pode forcar mesmo com away/offline). Reservado pra AGENT_ROLES.
 *
 * Valores: available | away | offline.
 * Default 'available' pra que ninguem apareca offline por engano no primeiro
 * acesso pos-deploy.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('agent_availability', 20)->default('available')->after('status');
            $table->timestamp('availability_changed_at')->nullable()->after('agent_availability');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['agent_availability', 'availability_changed_at']);
        });
    }
};
