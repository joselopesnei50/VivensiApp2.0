<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extrato de todas movimentações do saldo pré-pago WhatsApp por tenant.
 *
 * Types (enum):
 *   topup      — recarga (crédito, amount positivo). Fase 1 = manual pelo
 *                admin; Fase 2 = self-service via AbacatePay PIX
 *   debit      — cobrança por conversation billable (amount negativo).
 *                idempotente por whatsapp_conversation_id
 *   refund     — devolução (crédito, positivo). Ex: conversation Meta
 *                disputada + reversed
 *   adjustment — ajuste manual admin (positivo ou negativo). Reconciliação
 *                com fatura Meta, correção de bug, cortesia
 *
 * balance_after_brl_micros é o snapshot APÓS a transação — permite reconstruir
 * o saldo sem replay se o extrato ficar corrupto.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->enum('type', ['topup', 'debit', 'refund', 'adjustment']);
            $table->bigInteger('amount_brl_micros')->comment('Sinalizado — positivo pra crédito, negativo pra débito');
            $table->bigInteger('balance_after_brl_micros')->comment('Saldo APÓS aplicar esta transação (auditoria/reconstrução)');
            $table->string('description', 500)->nullable();

            // Idempotência de débito por conversation Meta
            $table->foreignId('whatsapp_conversation_id')->nullable()->constrained('whatsapp_conversations')->nullOnDelete();

            // Quem criou a transação (super_admin manual, ou null se automático via webhook)
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['tenant_id', 'created_at'], 'wact_tenant_created_idx');
            $table->index(['whatsapp_conversation_id', 'type'], 'wact_conv_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_credit_transactions');
    }
};
