<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registra o aceite versionado do Termo de Responsabilidade Anti-Ban
     * pelo gestor do tenant ANTES de criar uma instância Evolution.
     *
     * Itens registrados (proteção jurídica): versão do termo, hash do texto
     * vigente no momento, usuário, IP e user agent. Versão nova exige novo
     * aceite — basta aumentar 'current_version' em config('whatsapp.anti_ban_terms').
     */
    public function up(): void
    {
        Schema::create('whatsapp_anti_ban_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('version', 20);
            $table->string('terms_hash', 64);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('accepted_at')->useCurrent();
            $table->timestamps();

            // Um aceite por (tenant, versão) — versão nova invalida e exige novo aceite.
            $table->unique(['tenant_id', 'version'], 'wa_antiban_tenant_version_unique');
            $table->index('accepted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_anti_ban_acceptances');
    }
};
