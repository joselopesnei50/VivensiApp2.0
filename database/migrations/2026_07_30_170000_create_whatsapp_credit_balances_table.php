<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saldo pré-pago de WhatsApp por tenant. Uma linha por tenant, criada
 * on-demand na primeira recarga (ou quando admin ativa pré-pago).
 *
 * Modelo comercial A (Fase 1 MVP):
 *   - Vivensi cobra o cliente antes (BRL) e paga Meta depois (USD)
 *   - Débito automático a cada WhatsappConversation billable criada
 *     via ProcessCloudApiWebhook::upsertConversation
 *   - Envio bloqueado se saldo < worst_case (categoria marketing)
 *
 * prepaid_enabled_at é o opt-in por tenant — enquanto null, o tenant
 * opera em modelo B (só transparência, sem cobrança).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_credit_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();
            $table->bigInteger('balance_brl_micros')->default(0)->comment('Saldo em BRL micros (1_000_000 = R$ 1,00). Nunca fica negativo.');
            $table->timestamp('prepaid_enabled_at')->nullable()->comment('Quando admin ativou pré-pago pra esse tenant. Null = modelo B (sem cobrança).');
            $table->timestamp('low_balance_alerted_at')->nullable()->comment('Última vez que enviamos alerta de saldo baixo — evita spam.');
            $table->bigInteger('last_topup_brl_micros')->default(0)->comment('Valor da última recarga em BRL micros — base pro cálculo do threshold de alerta.');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_credit_balances');
    }
};
