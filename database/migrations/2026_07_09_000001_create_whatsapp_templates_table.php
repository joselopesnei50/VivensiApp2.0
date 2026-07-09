<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Templates de mensagens WhatsApp Cloud API — cache local sincronizado da Meta.
 *
 * Templates são a UNICA forma de iniciar conversas com clientes fora da janela
 * de 24h em Cloud API. Cada template pertence a uma WABA (via WhatsappInstance),
 * que por sua vez pertence a um tenant. Isolamento total por tenant.
 *
 * Fluxo: user cria localmente → POST na Graph API → status inicial PENDING →
 * Meta aprova/rejeita via webhook message_template_status_update → atualiza aqui.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_instance_id')->constrained('whatsapp_instances')->cascadeOnDelete();

            // Denormalizado pra evitar join em queries de listagem
            $table->string('waba_id')->index();

            // Identificação
            $table->string('meta_template_id')->nullable()->index()->comment('ID retornado pela Graph API — só populado após criação bem-sucedida');
            $table->string('name', 512)->comment('Nome do template (único por WABA + language na Meta)');
            $table->string('language', 20)->default('pt_BR')->comment('Ex: pt_BR, en_US');

            // Classificação Meta
            $table->enum('category', ['MARKETING', 'UTILITY', 'AUTHENTICATION'])->comment('Categoria exigida pela Meta pra billing por conversa');

            // Ciclo de vida
            $table->enum('status', ['LOCAL_DRAFT', 'PENDING', 'APPROVED', 'REJECTED', 'PAUSED', 'DISABLED'])->default('LOCAL_DRAFT');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('synced_at')->nullable()->comment('Última vez que sync com Meta atualizou este registro');

            // Payload completo pra render/preview
            $table->json('components')->comment('Estrutura header/body/footer/buttons no formato Meta');

            $table->timestamps();

            $table->unique(['whatsapp_instance_id', 'name', 'language'], 'whatsapp_templates_unique_per_wa_language');
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
