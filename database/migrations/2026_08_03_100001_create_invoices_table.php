<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Histórico de faturas (invoices) das assinaturas mensais dos tenants.
 *
 * Fluxo:
 *   1. Command `invoices:generate-recurring` roda dia 1 às 00:10, cria
 *      uma invoice `open` pra cada tenant active+não-cortesia
 *   2. Cliente vê em /minha-conta/faturas com CTA "Pagar via AbacatePay"
 *      + "Copiar chave PIX Vivensi"
 *   3. Webhook AbacatePay marca `paid_via=abacatepay` quando charge confirma
 *   4. Admin pode marcar manual "Recebi PIX" → paid_via=pix_manual
 *   5. Command `invoices:mark-overdue` roda 06:00 diário e marca `overdue`
 *      pra invoices open com due_date < now - 3d
 *
 * Idempotência: unique (tenant_id, period_start) — mesmo tenant não gera
 * 2 invoices pro mesmo mês.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();

            $table->unsignedInteger('amount_cents')->comment('Valor em centavos (BRL). 12990 = R$ 129,90');
            $table->string('description', 300);

            $table->enum('status', ['open', 'paid', 'canceled', 'overdue'])->default('open')->index();
            $table->date('period_start')->comment('Primeiro dia do ciclo que essa fatura cobre');
            $table->date('period_end')->comment('Último dia do ciclo');
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();

            $table->enum('paid_via', [
                'abacatepay',    // webhook AbacatePay confirmou
                'pix_manual',    // admin marcou "recebi PIX Vivensi"
                'manual_admin',  // admin marcou como paga sem canal específico
                'free_courtesy', // gerada e imediatamente marcada (planos cortesia — não gera hoje mas fica pro futuro)
            ])->nullable();

            // Referências AbacatePay (populadas quando gera checkout)
            $table->string('abacatepay_charge_id')->nullable()->index();
            $table->string('abacatepay_pix_url', 500)->nullable();
            $table->string('abacatepay_billing_url', 500)->nullable();

            // Quem marcou (se manual)
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Idempotência: 1 invoice por tenant por período
            $table->unique(['tenant_id', 'period_start'], 'invoices_tenant_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
