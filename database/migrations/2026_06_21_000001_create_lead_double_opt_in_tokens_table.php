<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P0.2 — Double opt-in via WhatsApp (Fase 5.2 do roadmap).
 *
 * Cada lead pendente recebe um token único; o webhook do Evolution
 * (ProcessEvolutionWebhook) confirma ou recusa quando a resposta casa
 * com palavras-chave de confirm/opt-out (config/whatsapp.php).
 *
 * Idempotência: scopeActive() filtra tokens pendentes não expirados.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('lead_double_opt_in_tokens')) {
            return;
        }

        Schema::create('lead_double_opt_in_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('opted_out_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'lead_id']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_double_opt_in_tokens');
    }
};
