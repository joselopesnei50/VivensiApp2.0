<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migração de modelo comercial: pré-pago (Fase 1) → cota mensal (Modelo C).
 *
 * Contexto: a Fase 1 pré-pago (commit 2d8c909) foi implementada com Vivensi
 * cobrando cliente e repassando pra Meta com markup. Análise posterior
 * mostrou que virar BSP oficial da Meta não faz sentido pro Vivensi hoje
 * (volume + risco fluxo caixa). Trocamos pro Modelo C: cliente continua
 * pagando Meta direto (transparência), Vivensi cobra assinatura mensal
 * pelo módulo. Cota inclusa por plano + packs extras vendidos avulso.
 *
 * Como a Fase 1 foi criada mas não teve nenhum tenant ativado, drop das
 * tabelas é seguro. Se por qualquer motivo já existe dado, exportar antes.
 *
 * Estrutura nova:
 *  - whatsapp_quota_usage: 1:1 tenant, contador mensal + pack extra atual
 *  - whatsapp_quota_events: log de cada consumo/pack/reset (auditoria)
 *  - subscription_plans: +3 colunas (cota inclusa, pack size, pack price BRL)
 */
return new class extends Migration {
    public function up(): void
    {
        // 1) Drop tabelas Fase 1 pré-pago (ninguém usa em produção)
        Schema::dropIfExists('whatsapp_credit_transactions');
        Schema::dropIfExists('whatsapp_credit_balances');

        // 2) Colunas novas em subscription_plans
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->unsignedInteger('whatsapp_conversations_included')->default(0)->after('capabilities')
                  ->comment('Cota mensal de conversas WhatsApp inclusas no plano. 0 = módulo desligado por padrão.');
            $table->unsignedInteger('whatsapp_extra_pack_size')->default(500)->after('whatsapp_conversations_included')
                  ->comment('Tamanho do pack extra de conversas vendido avulso.');
            $table->decimal('whatsapp_extra_pack_price_brl', 8, 2)->default(49.90)->after('whatsapp_extra_pack_size')
                  ->comment('Preço em BRL do pack extra (cobrado via AbacatePay na Fase 2 self-service).');
        });

        // 3) whatsapp_quota_usage — estado atual da cota por tenant
        Schema::create('whatsapp_quota_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();
            $table->unsignedInteger('conversations_used_month')->default(0)
                  ->comment('Contador de conversas billable usadas no mês corrente.');
            $table->unsignedInteger('extra_pack_conversations')->default(0)
                  ->comment('Saldo restante de packs extras comprados no mês corrente.');
            $table->unsignedInteger('plan_included_snapshot')->default(0)
                  ->comment('Snapshot da cota do plano no início do período (protege se admin trocar plano no meio do mês).');
            $table->date('period_start')->comment('Primeiro dia do mês corrente — resetado pelo command whatsapp:reset-monthly-quotas.');
            $table->timestamp('over_quota_alerted_at')->nullable()->comment('Última vez que enviamos alerta de cota estourada — evita spam.');
            $table->timestamps();

            $table->index('period_start', 'wqu_period_idx');
        });

        // 4) whatsapp_quota_events — log detalhado (auditoria + relatório cliente)
        Schema::create('whatsapp_quota_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->enum('type', ['consume', 'extra_pack', 'reset', 'adjustment']);
            $table->integer('conversations_delta')->comment('Sinalizado — negativo pra consume, positivo pra extra_pack/reset/adjustment.');
            $table->unsignedInteger('used_after')->comment('Snapshot de conversations_used_month após aplicar este evento.');
            $table->unsignedInteger('extra_pack_after')->comment('Snapshot de extra_pack_conversations após aplicar este evento.');
            $table->string('description', 500)->nullable();

            // Idempotência de consume por conversation Meta
            $table->foreignId('whatsapp_conversation_id')->nullable()->constrained('whatsapp_conversations')->nullOnDelete();

            // Quem gerou o evento (super_admin manual em extra_pack/adjustment, ou null se automático)
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['tenant_id', 'created_at'], 'wqe_tenant_created_idx');
            $table->index(['whatsapp_conversation_id', 'type'], 'wqe_conv_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_quota_events');
        Schema::dropIfExists('whatsapp_quota_usage');

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_conversations_included', 'whatsapp_extra_pack_size', 'whatsapp_extra_pack_price_brl']);
        });

        // Não recria as tabelas de pré-pago — se precisar, restaurar do commit 2d8c909.
    }
};
