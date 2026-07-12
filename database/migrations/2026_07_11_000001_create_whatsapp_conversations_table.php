<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unidade de faturamento Meta WhatsApp Cloud API.
 *
 * Meta cobra por CONVERSA (janela 24h agrupando N mensagens), não por mensagem.
 * Cada conversa tem uma categoria (marketing/utility/authentication/service) e um
 * preço em USD que Meta manda no webhook via campo `pricing`. Guardamos custo em
 * micros pra evitar float rounding (1 USD = 1.000.000 micros).
 *
 * Fase 5.1 (tracking only): populamos a partir do webhook; sem fatura/repasse ainda.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_instance_id')->constrained('whatsapp_instances')->cascadeOnDelete();

            $table->string('meta_conversation_id')->comment('conversation.id vindo do webhook Meta');
            $table->string('contact_wa_id', 32)->index()->comment('Numero do contato (E.164 sem +)');

            $table->enum('category', ['marketing', 'utility', 'authentication', 'service', 'referral_conversion'])
                ->comment('Categoria Meta que determina o preco');
            $table->string('origin_type', 40)
                ->nullable()
                ->comment('Como a conversa comecou. Meta usa varios valores (utility, marketing, business_initiated, etc)');

            $table->timestamp('expires_at')->nullable()->comment('Fim da janela 24h da conversa');
            $table->timestamp('started_at')->nullable()->comment('Primeiro evento de faturamento observado');

            $table->unsignedBigInteger('cost_usd_micros')->default(0)
                ->comment('Custo em USD micros. 1 USD = 1000000. $0.008 = 8000');
            $table->string('pricing_model', 10)->default('CBP')
                ->comment('CBP=conversation based, PMP=per message');
            $table->boolean('is_billable')->default(true)
                ->comment('Meta as vezes marca service intra-24h como nao cobravel');
            $table->string('country_code', 2)->nullable()
                ->comment('BR/US/etc. Populado do numero destino');

            $table->timestamps();

            $table->unique(['whatsapp_instance_id', 'meta_conversation_id'], 'whatsapp_conversations_unique_meta_id');
            $table->index(['tenant_id', 'started_at']);
            $table->index(['tenant_id', 'category', 'started_at']);
        });

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->foreignId('whatsapp_conversation_id')
                ->nullable()
                ->after('chat_id')
                ->constrained('whatsapp_conversations')
                ->nullOnDelete()
                ->comment('FK para faturamento Meta. Nullable: mensagens Evolution/inbound nao cobradas');
            $table->string('meta_pricing_category', 32)
                ->nullable()
                ->after('status')
                ->comment('Denormalizado da conversation p/ query rapida sem join');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropForeign(['whatsapp_conversation_id']);
            $table->dropColumn(['whatsapp_conversation_id', 'meta_pricing_category']);
        });

        Schema::dropIfExists('whatsapp_conversations');
    }
};
